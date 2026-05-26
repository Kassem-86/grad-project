<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ReminderResource;
use App\Models\Reminder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Kreait\Firebase\Auth;

class ReminderController extends Controller
{
    /**
     * Get all reminders for the authenticated user with status 'Still'.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $reminders = Reminder::where('user_id', $request->user()->id)
            ->where('status', 'Still')
            ->orderBy('time', 'asc')
            ->paginate(20);

        return ReminderResource::collection($reminders);
    }

    /**
     * Store a newly created reminder.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'message_type' => 'required|in:medication,glucose_check,meal',
            'medication_name' => 'nullable|string|max:255',
            'title' => 'nullable|string|max:255',
            'time' => 'nullable|date_format:Y-m-d H:i:s',
        ]);

        $reminder = $request->user()->reminders()->create([
            'message_type' => $validated['message_type'],
            'medication_name' => $validated['medication_name'] ?? null,
            'title' => $validated['title'],
            'time' => $validated['time'],
            'status' => 'Still',
        ]);
        $reminder->refresh();

        return response()->json([
            'message' => 'Reminder created successfully',
            'reminder' => new ReminderResource($reminder)
        ], 201);
    }

    /**
     * Update the status of a reminder.
     */
    public function updateStatus(Request $request, Reminder $reminder): JsonResponse
    {
        $this->authorize('update', $reminder);

        $validated = $request->validate([
            'status' => 'required|in:Still,Done,Skipped',
        ]);

        $reminder->update(['status' => $validated['status']]);

        return response()->json([
            'message' => 'Reminder status updated successfully',
            'reminder' => new ReminderResource($reminder),
        ]);
    }

    /**
     * Delete a reminder.
     */
    public function destroy(Reminder $reminder): JsonResponse
    {
        $this->authorize('delete', $reminder);

        $reminder->delete();

        return response()->json([
            'message' => 'Reminder deleted successfully',
        ]);
    }

    public function sync(\Illuminate\Http\Request $request)
    {
        $userId = $request->user()->id;
        $lastSync = $request->input('last_sync');
        $lastSyncTime = null;

        if ($lastSync) {
            $lastSyncTime = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $lastSync, 'Africa/Cairo')
                ->setTimezone(config('app.timezone'));
        }

        // Fetch Upserted Reminders
        $remindersQuery = \App\Models\Reminder::where('user_id', $userId);

        if ($lastSyncTime) {
            $remindersQuery->where('updated_at', '>', $lastSyncTime);
        }

        $upsertedReminders = $remindersQuery->get();

        // Fetch Deleted Reminder IDs
        $deletedQuery = \Illuminate\Support\Facades\DB::table('sync_deletions')
            ->where('user_id', $userId)
            ->where('table_name', 'reminders');

        if ($lastSyncTime) {
            $deletedQuery->where('deleted_at', '>', $lastSyncTime);
        }

        $deletedReminderIds = $deletedQuery->pluck('record_id');

        return response()->json([
            'upserted_reminders' => $upsertedReminders,
            'deleted_reminder_ids' => $deletedReminderIds,
        ], 200);
    }
}

