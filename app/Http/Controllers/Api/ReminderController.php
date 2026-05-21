<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ReminderResource;
use App\Models\Reminder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

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
            'title' => 'required|string|max:255',
            'message_type' => 'required|in:medication,glucose_check,meal',
            'medication_name' => 'required_if:message_type,medication|nullable|string|max:255',
            'time' => 'required|date_format:Y-m-d H:i:s',
        ]);

        $reminder = $request->user()->reminders()->create([
            'title' => $validated['title'],
            'message_type' => $validated['message_type'],
            'medication_name' => $validated['medication_name'] ?? null,
            'time' => $validated['time'],
            'status' => 'Still',
        ]);

        return response()->json([
            'message' => 'Reminder created successfully',
            'reminder' => new ReminderResource($reminder),
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
}
