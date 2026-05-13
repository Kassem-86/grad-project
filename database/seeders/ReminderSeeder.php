<?php

namespace Database\Seeders;

use App\Models\Reminder;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class ReminderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create 2 reminders at the current minute with status 'Still' (for immediate testing)
        Reminder::factory(2)
            ->for(\App\Models\User::find(1))
            ->atCurrentMinute()
            ->create();

        // Create 3 reminders in the past (some Done, some Skipped)
        Reminder::factory(3)
            ->for(\App\Models\User::find(1))
            ->inPast()
            ->create();

        // Create 3 reminders in the future (all Still)
        Reminder::factory(3)
            ->for(\App\Models\User::find(1))
            ->inFuture()
            ->create();

        // Create 2 more reminders with mixed times
        Reminder::factory(2)
            ->for(\App\Models\User::find(1))
            ->create();

        $this->command->info('✅ ReminderSeeder created 10 reminders for user_id = 1');
        $this->command->info('   - 2 reminders set to current minute with status "Still" (for testing)');
        $this->command->info('   - 3 reminders in the past');
        $this->command->info('   - 3 reminders in the future');
        $this->command->info('   - 2 reminders with mixed times');
    }
}
