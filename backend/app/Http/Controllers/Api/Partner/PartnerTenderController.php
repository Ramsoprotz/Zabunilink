<?php

namespace App\Http\Controllers\Api\Partner;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Location;
use App\Models\Tender;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class PartnerTenderController extends Controller
{
    /**
     * Paginated tender feed for API partners.
     *
     * Supports incremental sync via ?updated_since=ISO8601 so partners
     * only pull what changed since their last request.
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type'          => 'sometimes|in:government,private',
            'status'        => 'sometimes|in:open,closed,awarded',
            'category_id'   => 'sometimes|integer',
            'location_id'   => 'sometimes|integer',
            'updated_since' => 'sometimes|date',
            'per_page'      => 'sometimes|integer|min:1|max:200',
        ]);

        $query = Tender::with(['category:id,name,slug', 'location:id,name,slug'])
            ->where('is_published', true);

        if (! empty($validated['type'])) {
            $query->where('type', $validated['type']);
        }

        if (! empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        if (! empty($validated['category_id'])) {
            $query->where('category_id', $validated['category_id']);
        }

        if (! empty($validated['location_id'])) {
            $query->where('location_id', $validated['location_id']);
        }

        if (! empty($validated['updated_since'])) {
            $query->where('updated_at', '>=', Carbon::parse($validated['updated_since']));
        }

        $tenders = $query->orderBy('updated_at', 'desc')
            ->paginate($validated['per_page'] ?? 50);

        return response()->json([
            'data' => collect($tenders->items())->map(fn (Tender $t) => $this->transform($t)),
            'meta' => [
                'total'        => $tenders->total(),
                'per_page'     => $tenders->perPage(),
                'current_page' => $tenders->currentPage(),
                'last_page'    => $tenders->lastPage(),
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $tender = Tender::with(['category:id,name,slug', 'location:id,name,slug'])
            ->where('is_published', true)
            ->findOrFail($id);

        return response()->json(['data' => $this->transform($tender)]);
    }

    public function categories(): JsonResponse
    {
        return response()->json([
            'data' => Category::where('is_active', true)->get(['id', 'name', 'slug']),
        ]);
    }

    public function locations(): JsonResponse
    {
        return response()->json([
            'data' => Location::where('is_active', true)->get(['id', 'name', 'slug']),
        ]);
    }

    /**
     * Stable public contract — internal fields are deliberately excluded.
     */
    protected function transform(Tender $tender): array
    {
        return [
            'id'               => $tender->id,
            'title'            => $tender->title,
            'reference_number' => $tender->reference_number,
            'description'      => $tender->description,
            'organization'     => $tender->organization,
            'category'         => $tender->category?->only(['id', 'name', 'slug']),
            'location'         => $tender->location?->only(['id', 'name', 'slug']),
            'type'             => $tender->type,
            'value'            => $tender->value,
            'deadline'         => $tender->deadline?->toDateString(),
            'published_date'   => $tender->published_date?->toDateString(),
            'status'           => $tender->status,
            'documents_url'    => $tender->documents_url,
            'created_at'       => $tender->created_at?->toIso8601String(),
            'updated_at'       => $tender->updated_at?->toIso8601String(),
        ];
    }
}
