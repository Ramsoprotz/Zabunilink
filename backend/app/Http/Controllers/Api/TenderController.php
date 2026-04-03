<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\TenderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TenderController extends Controller
{
    public function __construct(
        protected TenderService $tenderService,
    ) {}

    /**
     * Get a paginated list of tenders with filters.
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['category_id', 'location_id', 'type', 'search', 'month']);
        $perPage = (int) $request->input('per_page', 15);

        $tenders = $this->tenderService->getTenders($filters, $perPage);

        return response()->json($tenders);
    }

    /**
     * Get a single tender by ID.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $tender = $this->tenderService->getTender($id);

        $isFavorited = false;
        if ($request->user()) {
            $isFavorited = $tender->favorites()
                ->where('user_id', $request->user()->id)
                ->exists();
        }

        $tenderData = $tender->toArray();
        $tenderData['is_favorited'] = $isFavorited;

        return response()->json([
            'data' => $tenderData,
        ]);
    }
}
