<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\SendTenderNotifications;
use App\Models\Tender;
use App\Models\TenderApplication;
use App\Models\TenderNotification;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class TendereeController extends Controller
{
    /**
     * List the authenticated user's posted tenders with application counts.
     */
    public function index(Request $request): JsonResponse
    {
        $tenders = Tender::with(['category', 'location'])
            ->withCount('applications')
            ->where('posted_by_user_id', $request->user()->id)
            ->latest()
            ->paginate($request->input('per_page', 15));

        return response()->json($tenders);
    }

    /**
     * Create a new tender posted by the authenticated user.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title'         => 'required|string|max:255',
            'description'   => 'required|string',
            'organization'  => 'required|string|max:255',
            'category_id'   => 'required|exists:categories,id',
            'location_id'   => 'required|exists:locations,id',
            'type'          => 'required|in:government,private,business',
            'value'         => 'nullable|numeric|min:0',
            'deadline'      => 'required|date|after:today',
            'requirements'  => 'nullable|string',
            'documents_url' => 'nullable|url',
            'contact_info'  => 'nullable|string',
        ]);

        $user = $request->user();

        // Default organization to user's business_name if not provided differently
        $validated['organization'] = $validated['organization'] ?? $user->business_name;

        $tender = Tender::create(array_merge($validated, [
            'source'            => 'business',
            'posted_by_user_id' => $user->id,
            'published_date'    => now()->toDateString(),
            'status'            => 'open',
            'is_published'      => true,
        ]));

        // Auto-set the ZabuniLink apply link as the documents URL
        $frontendUrl = rtrim(config('app.frontend_url', 'http://localhost:5173'), '/');
        $tender->update(['documents_url' => $frontendUrl . '/tenders/' . $tender->id]);

        $tender->load(['category', 'location']);

        // Notify matching users about this new business tender
        SendTenderNotifications::dispatch($tender);

        return response()->json([
            'message' => 'Tender created successfully.',
            'data'    => $tender,
        ], 201);
    }

    /**
     * Get a single tender posted by the authenticated user.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $tender = Tender::with(['category', 'location'])
            ->withCount('applications')
            ->where('posted_by_user_id', $request->user()->id)
            ->findOrFail($id);

        return response()->json(['data' => $tender]);
    }

    /**
     * Update one of the authenticated user's tenders.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $tender = Tender::where('posted_by_user_id', $request->user()->id)->findOrFail($id);

        $validated = $request->validate([
            'title'         => 'sometimes|required|string|max:255',
            'description'   => 'sometimes|required|string',
            'organization'  => 'sometimes|required|string|max:255',
            'category_id'   => 'sometimes|required|exists:categories,id',
            'location_id'   => 'sometimes|required|exists:locations,id',
            'type'          => 'sometimes|required|in:government,private,business',
            'value'         => 'nullable|numeric|min:0',
            'deadline'      => 'sometimes|required|date|after:today',
            'requirements'  => 'nullable|string',
            'documents_url' => 'nullable|url',
            'contact_info'  => 'nullable|string',
        ]);

        $tender->update($validated);
        $tender->load(['category', 'location']);

        return response()->json([
            'message' => 'Tender updated successfully.',
            'data'    => $tender,
        ]);
    }

    /**
     * Unpublish / soft-delete one of the authenticated user's tenders.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $tender = Tender::where('posted_by_user_id', $request->user()->id)->findOrFail($id);

        $tender->update(['is_published' => false]);

        return response()->json(['message' => 'Tender unpublished successfully.']);
    }

    /**
     * List applications received for one of the authenticated user's tenders.
     */
    public function applications(Request $request, int $tenderId): JsonResponse
    {
        // Verify the tender belongs to this user
        $tender = Tender::where('posted_by_user_id', $request->user()->id)->findOrFail($tenderId);

        $applications = TenderApplication::with([
            'user:id,name,email,phone,business_name',
        ])
            ->where('tender_id', $tender->id)
            ->latest()
            ->paginate($request->input('per_page', 20));

        return response()->json($applications);
    }

    /**
     * Update the status (and optional notes) of an application on the user's tender.
     * When status is 'awarded', all other applications for the same tender are rejected
     * and a TenderNotification is created for the applicant.
     */
    public function updateApplication(Request $request, int $tenderId, int $appId): JsonResponse
    {
        // Verify tender ownership
        $tender = Tender::where('posted_by_user_id', $request->user()->id)->findOrFail($tenderId);

        $application = TenderApplication::where('tender_id', $tender->id)->findOrFail($appId);

        $validated = $request->validate([
            'status'         => 'required|in:pending,shortlisted,awarded,rejected',
            'tenderee_notes' => 'nullable|string',
        ]);

        DB::transaction(function () use ($validated, $application, $tender) {
            $updates = [
                'status'         => $validated['status'],
                'tenderee_notes' => $validated['tenderee_notes'] ?? $application->tenderee_notes,
            ];

            if ($validated['status'] === 'awarded') {
                $updates['awarded_at'] = now();

                // Reject all other applications for this tender
                TenderApplication::where('tender_id', $tender->id)
                    ->where('id', '!=', $application->id)
                    ->update(['status' => 'rejected']);
            }

            $application->update($updates);

            // Send notification to the applicant
            $statusLabel = ucfirst($validated['status']);
            TenderNotification::create([
                'user_id'   => $application->user_id,
                'tender_id' => $tender->id,
                'type'      => 'application_status_update',
                'channel'   => 'in_app',
                'title'     => "Application {$statusLabel}",
                'message'   => "Your application for \"{$tender->title}\" has been {$validated['status']}.",
                'status'    => 'sent',
            ]);
        });

        $application->refresh()->load('user:id,name,email,phone,business_name');

        // Notify applicant of status change via email and SMS (using templates)
        $applicant = $application->user;
        if ($applicant) {
            app(NotificationService::class)->notifyApplicationStatus($tender, $applicant, $validated['status']);
        }

        return response()->json([
            'message' => 'Application status updated successfully.',
            'data'    => $application,
        ]);
    }

    /**
     * Upload documents to one of the authenticated user's tenders.
     */
    public function uploadDocuments(Request $request, int $id): JsonResponse
    {
        $tender = Tender::where('posted_by_user_id', $request->user()->id)->findOrFail($id);

        $request->validate([
            'documents'   => 'required|array|min:1',
            'documents.*' => 'file|mimes:pdf,doc,docx,jpg,jpeg,png|max:20480',
        ]);

        $existing = $tender->documents ?? [];

        foreach ($request->file('documents') as $file) {
            $path = $file->store("tenders/{$tender->id}", 'public');
            $existing[] = [
                'name'        => $file->getClientOriginalName(),
                'path'        => $path,
                'url'         => asset(Storage::url($path)),
                'size'        => $file->getSize(),
                'mime'        => $file->getMimeType(),
                'uploaded_at' => now()->toISOString(),
            ];
        }

        $tender->update(['documents' => $existing]);

        return response()->json([
            'message'   => 'Documents uploaded successfully.',
            'documents' => $existing,
        ]);
    }

    /**
     * Delete a specific document from one of the authenticated user's tenders.
     */
    public function deleteDocument(Request $request, int $id): JsonResponse
    {
        $tender = Tender::where('posted_by_user_id', $request->user()->id)->findOrFail($id);

        $request->validate(['path' => 'required|string']);

        $docs = collect($tender->documents ?? [])
            ->reject(fn($d) => $d['path'] === $request->path)
            ->values()
            ->all();

        Storage::disk('public')->delete($request->path);
        $tender->update(['documents' => $docs]);

        return response()->json(['message' => 'Document deleted.', 'documents' => $docs]);
    }

    /**
     * Return a summary of the authenticated user's tenderee activity.
     */
    public function stats(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $totalPosted = Tender::where('posted_by_user_id', $userId)->count();

        $totalApplicationsReceived = TenderApplication::whereHas(
            'tender',
            fn ($q) => $q->where('posted_by_user_id', $userId)
        )->count();

        $totalAwarded = TenderApplication::whereHas(
            'tender',
            fn ($q) => $q->where('posted_by_user_id', $userId)
        )->where('status', 'awarded')->count();

        $activeTenders = Tender::where('posted_by_user_id', $userId)
            ->where('status', 'open')
            ->where('is_published', true)
            ->where('deadline', '>=', now())
            ->count();

        return response()->json([
            'data' => [
                'total_posted'                => $totalPosted,
                'total_applications_received' => $totalApplicationsReceived,
                'total_awarded'               => $totalAwarded,
                'active_tenders'              => $activeTenders,
            ],
        ]);
    }
}
