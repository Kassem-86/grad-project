<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Log;
use App\Models\Glucose;
use App\Models\Meal;
use App\Models\RecordMedication;
use App\Models\SelectedMedication;
use Illuminate\Database\Seeder;

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

        // Create test data for each user
        foreach ($users->take(3) as $user) {
            // Create 3 logs per user with different dates
            for ($i = 0; $i < 3; $i++) {
                $this->createLogWithRelations($user, $i);
            }
        }

        $this->command->info('Logs and related health records seeded successfully!');
    }

    /**
     * Create a log with all related health records
     */
    private function createLogWithRelations(User $user, int $dayOffset): void
    {
        $loggedDate = now()->subDays($dayOffset);

        // Create a log entry
        $log = Log::create([
            'user_id' => $user->id,
            'log_title' => 'Daily Health Log - ' . $loggedDate->format('M d, Y'),
            'log_description' => 'Health tracking for ' . $loggedDate->format('l'),
            'logged_at' => $loggedDate,
        ]);

        // Create Glucose readings
        $this->createGlucoseRecords($log, $user, $loggedDate);

        // Create Meal records
        $this->createMealRecords($log, $user, $loggedDate);

        // Create Medication records
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
     */
    private function createMealRecords(Log $log, User $user, $loggedDate): void
    {
        $mealTypes = ['Breakfast', 'Lunch', 'Dinner', 'Snack'];
        $descriptions = [
            'Oatmeal with berries and honey',
            'Grilled chicken with brown rice and vegetables',
            'Whole wheat bread with salmon and salad',
            'Yogurt with granola and almonds',
        ];

        for ($i = 0; $i < 2; $i++) {
            Meal::create([
                'log_id' => $log->log_id,
                'user_id' => $user->id,
                'total_carb' => rand(30, 80),
                'total_calories' => rand(300, 800),
                'meal_type' => $mealTypes[array_rand($mealTypes)],
                'meal_description' => $descriptions[array_rand($descriptions)],
                'notes' => 'Test meal record ' . ($i + 1),
            ]);
        }
    }

    /**
     * Create medication records with selected medications for a log
     */
    private function createMedicationRecords(Log $log, User $user, $loggedDate): void
    {
        $medications = [
            ['name' => 'Metformin', 'dosage' => '500mg', 'frequency' => 'Twice Daily'],
            ['name' => 'Lisinopril', 'dosage' => '10mg', 'frequency' => 'Once Daily'],
            ['name' => 'Atorvastatin', 'dosage' => '20mg', 'frequency' => 'Once Daily'],
            ['name' => 'Aspirin', 'dosage' => '81mg', 'frequency' => 'Once Daily'],
            ['name' => 'Vitamin D3', 'dosage' => '2000IU', 'frequency' => 'Once Daily'],
        ];

        // Select random medications for this log
        $selectedMeds = array_slice($medications, 0, rand(2, 4));
        
        // Create RecordMedication with array of medications
        $recordMed = RecordMedication::create([
            'log_id' => $log->log_id,
            'user_id' => $user->id,
            'medications' => $selectedMeds,
            'notes' => 'Daily medication routine',
        ]);

        // Create SelectedMedication entries for each medication
        foreach ($selectedMeds as $med) {
            SelectedMedication::create([
                'medication_id' => $recordMed->medication_id,
                'log_id' => $log->log_id,
                'user_id' => $user->id,
                'medication_name' => $med['name'],
            ]);
        }
    }
}
