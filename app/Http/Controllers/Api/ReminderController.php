<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ReminderResource;
use App\Models\Reminder;
use App\Models\Notification; // تأكد من استيراد الموديل
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ReminderController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * Get all reminders and check for any due ones to create notifications.
     */
    public function index(Request $request)
    {
        // 1. تشغيل فحص التذكيرات فوراً قبل ما يعرض القائمة
        $this->checkAndNotify($request);

        $reminders = Reminder::where('user_id', $request->user()->id)
            ->where('status', 'Still')
            ->orderBy('time', 'asc')
            ->paginate(20);

        return ReminderResource::collection($reminders);
    }

    /**
     * التحقق من التذكيرات التي حان وقتها وتحويلها لإشعارات
     */
    public function checkAndNotify(Request $request)
    {
        $userId = $request->user()->id;
        $now = Carbon::now()->format('Y-m-d H:i:s');
// استبدل السطر اللي بيعمل الخطأ بـ:
\Log::info("Now: " . \Carbon\Carbon::now()->format('Y-m-d H:i:s'));        // جلب التذكيرات التي انتهى وقتها ولم يتم التعامل معها
        $dueReminders = Reminder::where('user_id', $userId)
            ->where('status', 'Still')
            ->where('time', '<=', $now)
            ->get();
            \Log::info("Found " . $dueReminders->count() . " reminders to process.");

        foreach ($dueReminders as $reminder) {
            // إنشاء إشعار
            Notification::create([
                'user_id' => $userId,
                'title' => 'time for ' . $reminder->message_type,
                'message' => 'Reminder: ' . ($reminder->title ?? $reminder->medication_name),
                'type' => 'reminder',
                'reference_id' => $reminder->id,
            ]);

            // تحديث الحالة لـ Done عشان ميتكررش الإشعار
            $reminder->update(['status' => 'Done']);        }
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
        'time' => 'required|date_format:Y-m-d H:i:s',
    ]);

    $reminder = $request->user()->reminders()->create([
        'message_type' => $validated['message_type'],
        'medication_name' => $validated['medication_name'] ?? null,
        'title' => $validated['title'],
        'time' => $validated['time'],
        'status' => 'Still',
    ]);

    // *** التعديل هنا: نادِ دالة الفحص فوراً بعد الحفظ ***
    // $this->checkAndNotify($request);

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
   
        return response()->json(['message' => 'Reminder deleted successfully']);
    }

    /**
     * Sync function remains as is...
     */
    public function sync(Request $request)
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

