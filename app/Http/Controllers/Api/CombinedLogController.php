<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Log;
use App\Models\Glucose;
use App\Models\Meal;
use App\Models\RecordMedication;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CombinedLogController extends Controller
{
    /**
     * Store a combined health log entry.
     */
    public function store(Request $request)
    {
        // 1. Validation
        $validated = $request->validate([
            'log_title' => 'nullable|string',
            'log_description' => 'nullable|string',
            'logged_at' => 'nullable|date_format:Y-m-d H:i:s',

            'recordGlucose' => 'nullable|array',
            'recordGlucose.glucose_level' => 'nullable|numeric',
            'recordGlucose.reading_type' => 'required_with:recordGlucose.glucose_level|string',
            'recordGlucose.a1c_estimation' => 'nullable',
            'recordGlucose.notes' => 'nullable|string',

            'recordMeal' => 'nullable|array',
            'recordMeal.meal_type' => 'nullable|in:Breakfast,Lunch,Dinner,Snack',
            'recordMeal.meal_description' => 'nullable|string',
            'recordMeal.total_calories' => 'required_with:recordMeal.meal_type|numeric',
            'recordMeal.total_carb' => 'required_with:recordMeal.meal_type|numeric',
            'recordMeal.notes' => 'nullable|string',

            'recordMedication' => 'nullable|array',
            'recordMedication.medications' => 'nullable|array',
            'recordMedication.notes' => 'nullable|string',
        ]);

        try {
            // 2. Database Transaction
            $result = DB::transaction(function () use ($request, $validated) {
                $userId = Auth::id();
                $loggedAt = !empty($validated['logged_at']) ? $validated['logged_at'] : now();

                // Create the parent Log record
                $log = Log::create([
                    'user_id' => $userId,
                    'log_title' => $validated['log_title'] ?? null,
                    'log_description' => $validated['log_description'] ?? null,
                    'logged_at' => $loggedAt,
                ]);

                // Create Glucose record (if glucose_level is present)
                $glucoseData = $request->input('recordGlucose');
                if (!empty($glucoseData['glucose_level'])) {
                    Glucose::create([
                        'log_id' => $log->log_id,
                        'user_id' => $userId,
                        'glucose_level' => $glucoseData['glucose_level'],
                        'reading_type' => $glucoseData['reading_type'],
                        'a1c_estimation' => $glucoseData['a1c_estimation'] ?? null,
                        'notes' => $glucoseData['notes'] ?? null,
                    ]);
                }

                // Create Meal record (if meal_type is present)
                $mealData = $request->input('recordMeal');
                if (!empty($mealData['meal_type'])) {
                    Meal::create([
                        'log_id' => $log->log_id,
                        'user_id' => $userId,
                        'meal_type' => $mealData['meal_type'],
                        'meal_description' => $mealData['meal_description'] ?? null,
                        'total_calories' => $mealData['total_calories'],
                        'total_carb' => $mealData['total_carb'],
                        'notes' => $mealData['notes'] ?? null,
                    ]);
                }

                // Create RecordMedication record (if medications array is present)
                $medicationData = $request->input('recordMedication');
                if (!empty($medicationData['medications']) && is_array($medicationData['medications'])) {
                    RecordMedication::create([
                        'log_id' => $log->log_id,
                        'user_id' => $userId,
                        'medications' => $medicationData['medications'],
                        'notes' => $medicationData['notes'] ?? null,
                    ]);
                }

                return $log;
            });

            // 3. Response
            return response()->json([
                'success' => true,
                'message' => 'Combined log saved successfully',
                'data' => $result
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
