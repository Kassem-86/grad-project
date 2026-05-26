<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Log;
use App\Models\Glucose;
use App\Models\Meal;
use App\Models\RecordMedication;
use App\Models\SelectedMedication;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class LogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get existing users or create test users
        $users = User::all();
        
        if ($users->isEmpty()) {
            $this->command->warn('No users found. Please run the DatabaseSeeder first.');
            return;
        }

        // First, seed selected medications for each user (done once per user, not per log)
        foreach ($users as $user) {
            $this->seedSelectedMedications($user);
        }

        // Then create unified health logs for each user
        foreach ($users as $user) {
            // Create 5-10 unified logs per user with different dates
            $logsPerUser = rand(5, 10);
            for ($i = 0; $i < $logsPerUser; $i++) {
                $this->createUnifiedLogWithRelations($user, $i);
            }
        }

        $this->command->info('Unified logs and related health records seeded successfully!');
    }

    /**
     * Seed selected medications for a user (done once per user)
     */
    private function seedSelectedMedications(User $user): void
    {
        $medicationNames = [
            'metformin',
            'lisinopril',
            'atorvastatin',
            'aspirin',
            'vitamin_d3',
            'amlodipine',
            'omeprazole',
        ];

        // Select 3-5 random medications for this user
        $medicationCount = rand(3, 5);
        $selectedIndices = array_rand($medicationNames, $medicationCount);
        
        // Handle case where array_rand returns a single value
        if (!is_array($selectedIndices)) {
            $selectedIndices = [$selectedIndices];
        }
        
        foreach ($selectedIndices as $index) {
            SelectedMedication::firstOrCreate(
                [
                    'user_id' => $user->id,
                    'medication_name' => $medicationNames[$index],
                ],
                [
                    'user_id' => $user->id,
                    'medication_name' => $medicationNames[$index],
                ]
            );
        }
    }

    /**
     * Create a unified log with all related health records (Glucose, Meal, Medication)
     * All records are linked via the same log_id to represent a single unified health event
     */
    private function createUnifiedLogWithRelations(User $user, int $dayOffset): void
    {
        $loggedDate = now()->subDays($dayOffset);

        // Create a parent log entry
        $log = Log::create([
            'log_id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'log_title' => 'Daily Health Log - ' . $loggedDate->format('M d, Y'),
            'log_description' => 'Unified health tracking for ' . $loggedDate->format('l'),
            'logged_at' => $loggedDate,
        ]);

        // Create a Glucose record linked to this specific log
        $this->createGlucoseRecords($log, $user, $loggedDate);

        // Create a Meal record linked to this specific log
        $this->createMealRecords($log, $user, $loggedDate);

        // Create a Medication record linked to this specific log
        $this->createMedicationRecords($log, $user, $loggedDate);
    }

    /**
     * Create glucose readings for a log
     */
    private function createGlucoseRecords(Log $log, User $user, $loggedDate): void
    {
        $readingTypes = ['Fasting', 'Before Meal', 'After Meal', 'Random'];

        // Only one glucose reading per log due to unique constraint on log_id
        Glucose::create([
            'log_id' => $log->log_id,
            'user_id' => $user->id,
            'glucose_level' => rand(90, 180),
            'reading_type' => $readingTypes[array_rand($readingTypes)],
            'notes' => 'Glucose reading for ' . $loggedDate->format('Y-m-d'),
            'a1c_estimation' => rand(5, 10),
            'average_glucose_level' => rand(100, 170),
        ]);
    }

    /**
     * Create meal records for a log
     * Creates 1-2 meal records per unified log entry
     */
    private function createMealRecords(Log $log, User $user, $loggedDate): void
    {
        $mealTypes = ['Breakfast', 'Lunch', 'Dinner', 'Snack'];
        $descriptions = [
            'Oatmeal with berries and honey',
            'Grilled chicken with brown rice and vegetables',
            'Whole wheat bread with salmon and salad',
            'Yogurt with granola and almonds',
            'Pasta with tomato sauce and vegetables',
            'Smoothie with banana and spinach',
        ];

        // Create 1-2 meals per log (representing multiple meals in a single health log)
        $mealCount = rand(1, 2);
        for ($i = 0; $i < $mealCount; $i++) {
            Meal::create([
                'log_id' => $log->log_id,
                'user_id' => $user->id,
                'total_carb' => rand(30, 80),
                'total_calories' => rand(300, 800),
                'meal_type' => $mealTypes[array_rand($mealTypes)],
                'meal_description' => $descriptions[array_rand($descriptions)],
                'notes' => 'Meal record ' . ($i + 1) . ' for ' . $loggedDate->format('Y-m-d'),
            ]);
        }
    }

    /**
     * Create medication records for a log
     * Simply creates the RecordMedication entry (pivot table linking is optional)
     */
    private function createMedicationRecords(Log $log, User $user, $loggedDate): void
    {
        // Create RecordMedication record
        RecordMedication::create([
            'log_id' => $log->log_id,
            'user_id' => $user->id,
            'notes' => 'Daily medication routine for ' . $loggedDate->format('Y-m-d'),
        ]);
        
        // Note: Pivot table linking via medication_log_pivot is optional and can be 
        // seeded separately if needed. The RecordMedication record alone is sufficient.
    }
}
