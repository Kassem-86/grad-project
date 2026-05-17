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
                    'log_id'          => (string) \Illuminate\Support\Str::uuid(), 
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

    /**
     * Store a combined health log entry with Android-generated UUID.
     * 
     * The Android client generates a UUID and sends it as log_id in the request.
     * This method explicitly uses the client-generated UUID for the parent Log and all child records.
     */
    public function storeWithAndroidId(Request $request)
    {
        // 1. Validation - log_id must be provided as a UUID string
        $validated = $request->validate([
            'log_id' => 'required|string|uuid',
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
            'recordMeal.total_calories' => 'nullable|numeric',
            'recordMeal.total_carb' => 'nullable|numeric',
            'recordMeal.notes' => 'nullable|string',

            'recordMedication' => 'nullable|array',
            'recordMedication.medications' => 'nullable|array',
            'recordMedication.notes' => 'nullable|string',
        ]);

        try {
            // 2. Database Transaction
            $result = DB::transaction(function () use ($request, $validated) {
                $userId = Auth::id();
                $logId = $validated['log_id']; // Use the Android-generated UUID
                $loggedAt = !empty($validated['logged_at']) ? $validated['logged_at'] : now();

                // Create the parent Log record with the provided log_id (UUID)
                $log = Log::create([
                    'log_id' => $logId,
                    'user_id' => $userId,
                    'log_title' => $validated['log_title'] ?? null,
                    'log_description' => $validated['log_description'] ?? null,
                    'logged_at' => $loggedAt,
                ]);

                // Create Glucose record (if glucose_level is present)
                $glucoseData = $request->input('recordGlucose');
                if (!empty($glucoseData['glucose_level'])) {
                    Glucose::create([
                        'log_id' => $logId,
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
                        'log_id' => $logId,
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
                        'log_id' => $logId,
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
                'message' => ' log saved successfully with Android ',
                'data' => $result
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString() // زود السطر ده مؤقتاً عشان يبان الكراش فين بالظبط
            ], 500);
        }
    }
    public function update(Request $request, Log $log)
    {
        // 1. Validation
        $validated = $request->validate([
            'log_title' => 'nullable|string',
            'log_description' => 'nullable|string',

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
            $result = DB::transaction(function () use ($request, $validated, $log) {
                $userId = Auth::id();

                // Update the parent Log record
                $log->update([
                    'log_title' => $validated['log_title'] ?? $log->log_title,
                    'log_descri9-ption' => $validated['log_description'] ?? $log->log_description,
                ]);

                // Handle Glucose record
                $glucoseData = $request->input('recordGlucose');
                if (!empty($glucoseData['glucose_level'])) {
                    // Update or create Glucose record
                    Glucose::updateOrCreate(
                        ['log_id' => $log->log_id],
                        [
                            'user_id' => $userId,
                            'glucose_level' => $glucoseData['glucose_level'],
                            'reading_type' => $glucoseData['reading_type'],
                            'a1c_estimation' => $glucoseData['a1c_estimation'] ?? null,
                            'notes' => $glucoseData['notes'] ?? null,
                        ]
                    );
                } else {
                    // Delete existing Glucose record if no data provided
                    Glucose::where('log_id', $log->log_id)->delete();
                }

                // Handle Meal record
                $mealData = $request->input('recordMeal');
                if (!empty($mealData['meal_type'])) {
                    // Update or create Meal record
                    Meal::updateOrCreate(
                        ['log_id' => $log->log_id],
                        [
                            'user_id' => $userId,
                            'meal_type' => $mealData['meal_type'],
                            'meal_description' => $mealData['meal_description'] ?? null,
                            'total_calories' => $mealData['total_calories'],
                            'total_carb' => $mealData['total_carb'],
                            'notes' => $mealData['notes'] ?? null,
                        ]
                    );
                } else {
                    // Delete existing Meal record if no data provided
                    Meal::where('log_id', $log->log_id)->delete();
                }

                // Handle RecordMedication record
                $medicationData = $request->input('recordMedication');
                if (!empty($medicationData['medications']) && is_array($medicationData['medications'])) {
                    // Update or create RecordMedication record
                    RecordMedication::updateOrCreate(
                        ['log_id' => $log->log_id],
                        [
                            'user_id' => $userId,
                            'medications' => $medicationData['medications'],
                            'notes' => $medicationData['notes'] ?? null,
                        ]
                    );
                } else {
                    // Delete existing RecordMedication record if no data provided
                    RecordMedication::where('log_id', $log->log_id)->delete();
                }

                // Reload log with all relations
                $log->load('recordGlucoses', 'recordMeals', 'recordMedications');

                return $log;
            });

            // 3. Response
            return response()->json([
                'success' => true,
                'message' => 'Log updated successfully',
                'data' => $result
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a combined health log entry and all associated records.
     */
    public function destroy(Log $log)
    {
        try {
            // 1. Database Transaction
            DB::transaction(function () use ($log) {
                // Delete associated records first
                Glucose::where('log_id', $log->log_id)->delete();
                Meal::where('log_id', $log->log_id)->delete();
                RecordMedication::where('log_id', $log->log_id)->delete();

                // Delete the parent Log record
                $log->delete();
            });

            // 2. Response
            return response()->json([
                'success' => true,
                'message' => 'Log and all associated records deleted successfully'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all combined logs for the authenticated user.
     */
    

    /**
     * Get a specific combined log with all associated records.
     */public function show(Request $request)
{
    try {
        $userId = Auth::id();
        
        // 1. بنعمل eager load للعلاقات الجديدة (المفرد والجمع حسب تعديل الموديل)
        // ملحوظة: لو سبت الـ medication كـ hasMany سيب اسمها بالجمع recordMedications
        $query = Log::where('user_id', $userId)
            ->with(['recordGlucose', 'recordMeal', 'recordMedication']);

        // 2. فلترة بالتاريخ لو مبعوت في الـ Request
        if ($request->has('date')) {
            $date = $request->query('date');
            $query->whereDate('logged_at', $date);
        }

        // 3. بنستخدم ->get() عشان يرجع "كل اللوجز" كـ مصفوفة (Array) بناءً على طلبه
        $logs = $query->orderBy('logged_at', 'desc')->get();

        // 4. الـ Response النهائي
        return response()->json([
            'success' => true,
            'message' => $request->has('date') 
                ? "Logs retrieved successfully for date: " . $request->query('date')
                : 'All user logs retrieved successfully',
            'data' => $logs // دي هترجع Array (لأنها مجموعة لوجز)، وجواها العلاقات objects
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => $e->getMessage()
        ], 500);
    }
}
}