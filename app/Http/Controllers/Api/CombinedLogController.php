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
        // 1. Validation (تعديل الـ Keys لـ snake_case)
        $validated = $request->validate([
            'log_title' => 'nullable|string',
            'log_description' => 'nullable|string',
            'logged_at' => 'nullable|date_format:Y-m-d H:i:s',

            'record_glucose' => 'nullable|array',
            'record_glucose.glucose_level' => 'nullable|numeric',
            'record_glucose.reading_type' => 'required_with:record_glucose.glucose_level|string',
            'record_glucose.a1c_estimation' => 'nullable',
            'record_glucose.notes' => 'nullable|string',

            'record_meal' => 'nullable|array',
            'record_meal.meal_type' => 'nullable|in:Breakfast,Lunch,Dinner,Snack',
            'record_meal.meal_description' => 'nullable|string',
            'record_meal.total_calories' => 'required_with:record_meal.meal_type|numeric',
            'record_meal.total_carb' => 'required_with:record_meal.meal_type|numeric',
            'record_meal.notes' => 'nullable|string',

            'record_medication' => 'nullable|array',
            'record_medication.medications' => 'nullable|array',
            'record_medication.notes' => 'nullable|string',
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

                // Create Glucose record
                $glucoseData = $request->input('record_glucose');
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

                // Create Meal record
                $mealData = $request->input('record_meal');
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

                // Create RecordMedication record
                $medicationData = $request->input('record_medication');
                if (!empty($medicationData['medications']) && is_array($medicationData['medications'])) {
                    RecordMedication::create([
                        'log_id' => $log->log_id,
                        'user_id' => $userId,
                        'medications' => $medicationData['medications'],
                        'notes' => $medicationData['notes'] ?? null,
                    ]);
                }

                // بنرجع الـ log مع العلاقات بالـ ديفولت الـ snake_case بتاع لارافيل
                return $log->load(['recordGlucose', 'recordMeal', 'recordMedication']);
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
     */
    public function storeWithAndroidId(Request $request)
    {
        // 1. Validation
        $validated = $request->validate([
            'log_id' => 'required|string|uuid',
            'log_title' => 'nullable|string',
            'log_description' => 'nullable|string',
            'logged_at' => 'nullable|date_format:Y-m-d H:i:s',

            'record_glucose' => 'nullable|array',
            'record_glucose.glucose_level' => 'nullable|numeric',
            'record_glucose.reading_type' => 'required_with:record_glucose.glucose_level|string',
            'record_glucose.a1c_estimation' => 'nullable',
            'record_glucose.notes' => 'nullable|string',

            'record_meal' => 'nullable|array',
            'record_meal.meal_type' => 'nullable|in:Breakfast,Lunch,Dinner,Snack',
            'record_meal.meal_description' => 'nullable|string',
            'record_meal.total_calories' => 'nullable|numeric',
            'record_meal.total_carb' => 'nullable|numeric',
            'record_meal.notes' => 'nullable|string',

            'record_medication' => 'nullable|array',
            'record_medication.medications' => 'nullable|array',
            'record_medication.notes' => 'nullable|string',
        ]);

        try {
            // 2. Database Transaction
            $result = DB::transaction(function () use ($request, $validated) {
                $userId = Auth::id();
                $logId = $validated['log_id'];
                $loggedAt = !empty($validated['logged_at']) ? $validated['logged_at'] : now();

                // Create the parent Log record
                $log = Log::create([
                    'log_id' => $logId,
                    'user_id' => $userId,
                    'log_title' => $validated['log_title'] ?? null,
                    'log_description' => $validated['log_description'] ?? null,
                    'logged_at' => $loggedAt,
                ]);

                // Create Glucose record
                $glucoseData = $request->input('record_glucose');
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

                // Create Meal record
                $mealData = $request->input('record_meal');
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

                // Create RecordMedication record
                $medicationData = $request->input('record_medication');
                if (!empty($medicationData['medications']) && is_array($medicationData['medications'])) {
                    RecordMedication::create([
                        'log_id' => $logId,
                        'user_id' => $userId,
                        'medications' => $medicationData['medications'],
                        'notes' => $medicationData['notes'] ?? null,
                    ]);
                }

                return $log->load(['recordGlucose', 'recordMeal', 'recordMedication']);
            });

            return response()->json([
                'success' => true,
                'message' => 'log saved successfully with Android',
                'data' => $result
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString() 
            ], 500);
        }
    }

    public function update(Request $request, Log $log)
    {
        // 1. Validation
        $validated = $request->validate([
            'log_title' => 'nullable|string',
            'log_description' => 'nullable|string',

            'record_glucose' => 'nullable|array',
            'record_glucose.glucose_level' => 'nullable|numeric',
            'record_glucose.reading_type' => 'required_with:record_glucose.glucose_level|string',
            'record_glucose.a1c_estimation' => 'nullable',
            'record_glucose.notes' => 'nullable|string',

            'record_meal' => 'nullable|array',
            'record_meal.meal_type' => 'nullable|in:Breakfast,Lunch,Dinner,Snack',
            'record_meal.meal_description' => 'nullable|string',
            'record_meal.total_calories' => 'required_with:record_meal.meal_type|numeric',
            'record_meal.total_carb' => 'required_with:record_meal.meal_type|numeric',
            'record_meal.notes' => 'nullable|string',

            'record_medication' => 'nullable|array',
            'record_medication.medications' => 'nullable|array',
            'record_medication.notes' => 'nullable|string',
        ]);

        try {
            // 2. Database Transaction
            $result = DB::transaction(function () use ($request, $validated, $log) {
                $userId = Auth::id();

                // Update the parent Log record
                $log->update([
                    'log_title' => $validated['log_title'] ?? $log->log_title,
                    'log_description' => $validated['log_description'] ?? $log->log_description,
                ]);

                // Handle Glucose record
                $glucoseData = $request->input('record_glucose');
                if (!empty($glucoseData['glucose_level'])) {
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
                    Glucose::where('log_id', $log->log_id)->delete();
                }

                // Handle Meal record
                $mealData = $request->input('record_meal');
                if (!empty($mealData['meal_type'])) {
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
                    Meal::where('log_id', $log->log_id)->delete();
                }

                // Handle RecordMedication record
                $medicationData = $request->input('record_medication');
                if (!empty($medicationData['medications']) && is_array($medicationData['medications'])) {
                    RecordMedication::updateOrCreate(
                        ['log_id' => $log->log_id],
                        [
                            'user_id' => $userId,
                            'medications' => $medicationData['medications'],
                            'notes' => $medicationData['notes'] ?? null,
                        ]
                    );
                } else {
                    RecordMedication::where('log_id', $log->log_id)->delete();
                }

                // الـ Reload العادي
                $log->load(['recordGlucose', 'recordMeal', 'recordMedication']);

                return $log;
            });

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
            DB::transaction(function () use ($log) {
                Glucose::where('log_id', $log->log_id)->delete();
                Meal::where('log_id', $log->log_id)->delete();
                RecordMedication::where('log_id', $log->log_id)->delete();
                $log->delete();
            });

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
     * Get a specific combined log with all associated records.
     */
    public function show(Request $request)
    {
        try {
            $userId = Auth::id();
            
            // شيلنا الـ "as" ورجعنا للـ default الـ snake_case
            $query = Log::where('user_id', $userId)
                ->with(['recordGlucose', 'recordMeal', 'recordMedication']);

            if ($request->has('date')) {
                $date = $request->query('date');
                $query->whereDate('logged_at', $date);
            }

            $logs = $query->orderBy('logged_at', 'desc')->get();

            return response()->json([
                'success' => true,
                'message' => $request->has('date') 
                    ? "Logs retrieved successfully for date: " . $request->query('date')
                    : 'All user logs retrieved successfully',
                'data' => $logs 
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

 public function sync(Request $request)
{
    try {
        $userId = Auth::id();
        
        $query = Log::where('user_id', $userId)
            ->with(['recordGlucose', 'recordMeal', 'recordMedication']);

        // التعديل السحري: $request->input() بتقرأ من الـ URL ومن الـ Body في نفس الوقت عشان نضمن إننا لقطنا التاريخ
        if ($request->has('last_sync') && !empty($request->input('last_sync'))) {
            $lastSyncRaw = $request->input('last_sync'); // هيلقطها مبعوتة كدة أو كدة
            
            try {
                // بنحول الاسترينج لـ Carbon object عشان قاعدة البيانات تفهمه صح
                $lastSync = \Carbon\Carbon::parse($lastSyncRaw);
                
                // الفلترة الصريحة
                $query->where('updated_at', '>', $lastSync);
            } catch (\Exception $e) {
                // fallback لو حصل أي لغبطة في الـ parsing
            }
        }

        $updatedLogs = $query->orderBy('updated_at', 'asc')->get();

        return response()->json([
            'success' => true,
            'message' => 'Sync data retrieved successfully',
            'data' => $updatedLogs 
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => $e->getMessage()
        ], 500);
    }
}

    public function getLogById($log_id)
    {
        try {
            $userId = Auth::id();

            $log = Log::where('user_id', $userId)
                ->where('log_id', $log_id)
                ->with(['recordGlucose', 'recordMeal', 'recordMedication']) 
                ->first();

            if (!$log) {
                return response()->json([
                    'success' => false,
                    'message' => 'Log not found',
                    'data' => null
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Log retrieved successfully',
                'data' => $log
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}