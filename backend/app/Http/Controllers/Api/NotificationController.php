<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\NotificationPreference;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * Get the authenticated user's notifications.
     */
    public function index(Request $request): JsonResponse
    {
        $notifications = $request->user()
            ->tenderNotifications()
            ->with('tender')
            ->latest()
            ->paginate($request->input('per_page', 15));

        return response()->json($notifications);
    }

    /**
     * Get the authenticated user's notification preferences.
     */
    public function preferences(Request $request): JsonResponse
    {
        $preferences = $request->user()->notificationPreference;

        if (! $preferences) {
            $preferences = NotificationPreference::create([
                'user_id' => $request->user()->id,
                'email_enabled' => true,
                'sms_enabled' => false,
                'push_enabled' => true,
                'category_ids' => [],
                'location_ids' => [],
            ]);
        }

        return response()->json([
            'data' => $preferences,
        ]);
    }

    /**
     * Update the authenticated user's notification preferences.
     */
    public function updatePreferences(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email_enabled' => 'sometimes|boolean',
            'sms_enabled' => 'sometimes|boolean',
            'push_enabled' => 'sometimes|boolean',
            'locale' => 'sometimes|string|in:en,sw',
            'category_ids' => 'sometimes|array',
            'category_ids.*' => 'integer|exists:categories,id',
            'location_ids' => 'sometimes|array',
            'location_ids.*' => 'integer|exists:locations,id',
        ]);

        $preferences = $request->user()->notificationPreference;

        if (! $preferences) {
            $preferences = NotificationPreference::create(array_merge(
                [
                    'user_id' => $request->user()->id,
                    'email_enabled' => true,
                    'sms_enabled' => false,
                    'push_enabled' => true,
                    'category_ids' => [],
                    'location_ids' => [],
                ],
                $validated,
            ));
        } else {
            $preferences->update($validated);
        }

        return response()->json([
            'message' => 'Notification preferences updated successfully.',
            'data' => $preferences->fresh(),
        ]);
    }

    /**
     * Mark a notification as read/sent.
     */
    public function markRead(Request $request, int $id): JsonResponse
    {
        $notification = $request->user()
            ->tenderNotifications()
            ->findOrFail($id);

        $notification->update([
            'status' => 'read',
            'sent_at' => $notification->sent_at ?? now(),
        ]);

        return response()->json([
            'message' => 'Notification marked as read.',
            'notification' => $notification->fresh(),
        ]);
    }
}
