<?php

namespace App\Console\Commands;

use App\Models\Reminder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class CheckReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reminders:check';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for reminders that should trigger and log them';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        // Get the current time, rounded to the minute (ignore seconds)
        $now = Carbon::now();
        $startOfMinute = $now->copy()->startOfMinute();
        $endOfMinute = $now->copy()->endOfMinute();

        // Find all reminders that match the current minute and have status 'Still'
        $reminders = Reminder::where('status', 'Still')
            ->whereBetween('time', [$startOfMinute, $endOfMinute])
            ->with('user')
            ->get();

        if ($reminders->isEmpty()) {
            $this->info('No reminders to process at ' . $now->format('Y-m-d H:i:s'));
            return 0;
        }

        // Log and mark each reminder as Done
        foreach ($reminders as $reminder) {
            Log::info('Reminder triggered', [
                'reminder_id' => $reminder->id,
                'user_id' => $reminder->user_id,
                'message_type' => $reminder->message_type,
                'time' => $reminder->time->format('Y-m-d H:i:s'),
                'user_name' => $reminder->user?->name ?? 'Unknown',
            ]);

            // Update the reminder status to 'Done' after logging
            $reminder->update(['status' => 'Done']);

            $this->info("Logged reminder {$reminder->id} for user {$reminder->user_id}");
        }

        $this->info("Processed " . $reminders->count() . " reminder(s)");

        return 0;
    }
}
