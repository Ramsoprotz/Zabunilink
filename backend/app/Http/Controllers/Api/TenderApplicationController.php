<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tender;
use App\Models\TenderApplication;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TenderApplicationController extends Controller
{
    /**
     * Get the authenticated user's tender applications.
     */
    public function index(Request $request): JsonResponse
    {
        $query = $request->user()
            ->tenderApplications()
            ->with('tender')
            ->latest();

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $applications = $query->paginate($request->input('per_page', 15));

        return response()->json($applications);
    }

    /**
     * Create a new tender application.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tender_id' => 'required|integer|exists:tenders,id',
            'notes' => 'nullable|string|max:2000',
            'type' => 'nullable|string|in:direct,assisted',
        ]);

        $user = $request->user();
        $tender = Tender::findOrFail($validated['tender_id']);

        // Business-posted tenders are open to all authenticated users.
        // External/admin tenders require a Pro or Business subscription.
        if ($tender->source !== 'business' && !$user->isPro()) {
            return response()->json([
                'message' => 'Pro subscription required to apply for this tender.',
            ], 403);
        }

        // Check if user already applied to this tender
        $existing = TenderApplication::where('user_id', $user->id)
            ->where('tender_id', $validated['tender_id'])
            ->first();

        if ($existing) {
            return response()->json([
                'message' => 'You have already applied to this tender.',
            ], 409);
        }

        // "assisted" = Let us apply for you (Pro feature), starts as pending
        // "direct" = Apply yourself, starts as submitted
        $type = $validated['type'] ?? 'direct';
        $status = $type === 'assisted' ? 'pending' : 'submitted';

        $application = TenderApplication::create([
            'user_id' => $user->id,
            'tender_id' => $validated['tender_id'],
            'notes' => $validated['notes'] ?? null,
            'status' => $status,
            'submitted_at' => now(),
            'documents' => [],
        ]);

        // Notify the business owner when their tender gets a new application
        $tender->load('postedBy');
        $owner = $tender->postedBy;
        if ($owner) {
            app(NotificationService::class)->notifyNewApplication($tender, $owner, $user, $application);
        }

        return response()->json([
            'message' => 'Application submitted successfully.',
            'data' => $application->load('tender'),
        ], 201);
    }

    /**
     * Get a single tender application.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $application = $request->user()
            ->tenderApplications()
            ->with('tender')
            ->findOrFail($id);

        return response()->json([
            'data' => $application,
        ]);
    }

    /**
     * Upload documents for a tender application.
     */
    public function uploadDocuments(Request $request, int $id): JsonResponse
    {
        $application = $request->user()
            ->tenderApplications()
            ->findOrFail($id);

        $request->validate([
            'documents' => 'required|array|min:1',
            'documents.*' => 'file|mimes:pdf,doc,docx,jpg,jpeg,png|max:10240',
        ]);

        $storedPaths = $application->documents ?? [];

        foreach ($request->file('documents') as $file) {
            $path = $file->store('applications/' . $application->id, 'public');
            $storedPaths[] = [
                'name'        => $file->getClientOriginalName(),
                'path'        => $path,
                'url'         => asset(Storage::url($path)),
                'size'        => $file->getSize(),
                'mime'        => $file->getMimeType(),
                'uploaded_at' => now()->toISOString(),
            ];
        }

        $application->update([
            'documents' => $storedPaths,
        ]);

        return response()->json([
            'message' => 'Documents uploaded successfully.',
            'application' => $application->fresh(),
        ]);
    }
}
