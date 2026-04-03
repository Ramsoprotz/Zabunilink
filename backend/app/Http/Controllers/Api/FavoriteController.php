<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Favorite;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FavoriteController extends Controller
{
    /**
     * Get the authenticated user's favorited tenders.
     */
    public function index(Request $request): JsonResponse
    {
        $favorites = $request->user()
            ->favorites()
            ->with(['tender.category', 'tender.location'])
            ->latest()
            ->paginate($request->input('per_page', 15));

        return response()->json($favorites);
    }

    /**
     * Add a tender to favorites.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tender_id' => 'required|integer|exists:tenders,id',
        ]);

        $user = $request->user();

        $existing = Favorite::where('user_id', $user->id)
            ->where('tender_id', $validated['tender_id'])
            ->first();

        if ($existing) {
            return response()->json([
                'message' => 'Tender is already in your favorites.',
            ], 409);
        }

        $favorite = Favorite::create([
            'user_id' => $user->id,
            'tender_id' => $validated['tender_id'],
        ]);

        return response()->json([
            'message' => 'Tender added to favorites.',
            'data' => $favorite->load(['tender.category', 'tender.location']),
        ], 201);
    }

    /**
     * Remove a tender from favorites.
     */
    public function destroy(Request $request, int $tenderId): JsonResponse
    {
        $deleted = $request->user()
            ->favorites()
            ->where('tender_id', $tenderId)
            ->delete();

        if (! $deleted) {
            return response()->json([
                'message' => 'Favorite not found.',
            ], 404);
        }

        return response()->json([
            'message' => 'Tender removed from favorites.',
        ]);
    }
}
