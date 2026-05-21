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
        'record_meal.total_calories' => 'nullable|numeric',
        'record_meal.total_carb' => 'nullable|numeric',
        'record_meal.notes' => 'nullable|string',

        'record_medication' => 'nullable|array',
        'record_medication.medications' => 'nullable|array', // 👈 مصفوفة أسامي سادة
        'record_medication.medications.*' => 'string',       // كل عنصر عبارة عن نص
        'record_medication.notes' => 'nullable|string',
    ]);

    try {
        // 2. Database Transaction (بترجع الـ ID بس)
        $logId = DB::transaction(function () use ($request, $validated) {
            $userId = Auth::id();
            $loggedAt = !empty($validated['logged_at']) ? $validated['logged_at'] : now();

            // Create the parent Log record
            $log = Log::create([
                'log_id'          => (string) \Illuminate\Support\Str::uuid(), 
                'user_id'         => $userId,
                'log_title'       => $validated['log_title'] ?? null,
                'log_description' => $validated['log_description'] ?? null,
                'logged_at'       => $loggedAt,
            ]);

            // Create Glucose record
            $glucoseData = $validated['record_glucose'] ?? null;
            if (!empty($glucoseData['glucose_level'])) {
                Glucose::create([
                    'log_id'         => $log->log_id,
                    'user_id'        => $userId,
                    'glucose_level'  => $glucoseData['glucose_level'],
                    'reading_type'   => $glucoseData['reading_type'],
                    'a1c_estimation' => $glucoseData['a1c_estimation'] ?? null,
                    'notes'          => $glucoseData['notes'] ?? null,
                ]);
            }

            // Create Meal record
            $mealData = $validated['record_meal'] ?? null;
            if (!empty($mealData['meal_type'])) {
                Meal::create([
                    'log_id'           => $log->log_id,
                    'user_id'          => $userId,
                    'meal_type'        => $mealData['meal_type'],
                    'meal_description' => $mealData['meal_description'] ?? null,
                    'total_calories'   => $mealData['total_calories'],
                    'total_carb'       => $mealData['total_carb'],
                    'notes'            => $mealData['notes'] ?? null,
                ]);
            }

            // ─── Handle RecordMedication ───
            $medicationData = $validated['record_medication'] ?? null;
            if (!empty($medicationData)) {
                $recordMedication = RecordMedication::create([
                    'log_id'  => $log->log_id,
                    'user_id' => $userId,
                    'notes'   => $medicationData['notes'] ?? null,
                ]);

                if (!empty($medicationData['medications']) && is_array($medicationData['medications'])) {
                    foreach ($medicationData['medications'] as $medName) {
                        \App\Models\SelectedMedication::create([
                            'medication_id'   => $recordMedication->medication_id,
                            'medication_name' => $medName,
                            'user_id'         => $userId
                        ]);
                    }
                }
            }

            return $log->log_id;
        });

        // 👈 بنقرا اللوج فريش من الداتابيز بعد الـ Commit عشان الـ UUID يربط صح
        $finalLog = Log::with(['recordGlucose', 'recordMeal', 'recordMedication.selectedMedications'])->find($logId);

        // 3. Response
        return response()->json([
            'success' => true,
            'message' => 'Combined log saved successfully',
            'data' => $finalLog
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
        'record_medication.medications.*' => 'string',
        'record_medication.notes' => 'nullable|string',
    ]);

    try {
        $resultData = DB::transaction(function () use ($request, $validated) {
            $userId = Auth::id();
            $logId = $validated['log_id'];
            $loggedAt = !empty($validated['logged_at']) ? $validated['logged_at'] : now();

            // ─── ON CONFLICT STRATEGY (UPSERT) ───
            $log = Log::where('log_id', $logId)->where('user_id', $userId)->first();

            if ($log) {
                $log->update([
                    'log_title' => $validated['log_title'] ?? $log->log_title,
                    'log_description' => $validated['log_description'] ?? $log->log_description,
                    'logged_at' => $loggedAt,
                ]);
                $message = 'Log updated successfully on conflict';
            } else {
                $log = Log::create([
                    'log_id' => $logId,
                    'user_id' => $userId,
                    'log_title' => $validated['log_title'] ?? null,
                    'log_description' => $validated['log_description'] ?? null,
                    'logged_at' => $loggedAt,
                ]);
                $message = 'Log saved successfully with Android';
            }

            // ─── 1. GLUCOSE UPSERT ───
            $glucoseData = $request->input('record_glucose');
            if (!empty($glucoseData['glucose_level'])) {
                Glucose::updateOrCreate(
                    ['log_id' => $logId, 'user_id' => $userId],
                    [
                        'glucose_level' => $glucoseData['glucose_level'],
                        'reading_type' => $glucoseData['reading_type'],
                        'a1c_estimation' => $glucoseData['a1c_estimation'] ?? null,
                        'notes' => $glucoseData['notes'] ?? null,
                    ]
                );
            } else {
                Glucose::where('log_id', $logId)->delete();
            }

            // ─── 2. MEAL UPSERT ───
            $mealData = $request->input('record_meal');
            if (!empty($mealData['meal_type'])) {
                Meal::updateOrCreate(
                    ['log_id' => $logId, 'user_id' => $userId],
                    [
                        'meal_type' => $mealData['meal_type'],
                        'meal_description' => $mealData['meal_description'] ?? null,
                        'total_calories' => $mealData['total_calories'] ?? 0,
                        'total_carb' => $mealData['total_carb'] ?? 0,
                        'notes' => $mealData['notes'] ?? null,
                    ]
                );
            } else {
                Meal::where('log_id', $logId)->delete();
            }

            // ─── 3. MEDICATION UPSERT ───
            $medicationData = $request->input('record_medication');
            if (!empty($medicationData)) {
                $recordMedication = RecordMedication::updateOrCreate(
                    ['log_id' => $logId, 'user_id' => $userId],
                    ['notes' => $medicationData['notes'] ?? null]
                );

                // مسح القديم
                \App\Models\SelectedMedication::where('medication_id', $recordMedication->medication_id)->delete();

                // إدخال الجديد بالاسم
                if (!empty($medicationData['medications']) && is_array($medicationData['medications'])) {
                    foreach ($medicationData['medications'] as $medName) {
                        \App\Models\SelectedMedication::create([
                            'medication_id'   => $recordMedication->medication_id,
                            'medication_name' => $medName,
                            'user_id'         => $userId
                        ]);
                    }
                }
            } else {
                $recordMedication = RecordMedication::where('log_id', $logId)->first();
                if ($recordMedication) {
                    \App\Models\SelectedMedication::where('medication_id', $recordMedication->medication_id)->delete();
                    $recordMedication->delete();
                }
            }

            return ['log_id' => $logId, 'message' => $message];
    });

        // 👈 جلب الداتا فريش برة الـ Transaction
        $finalLog = Log::with(['recordGlucose', 'recordMeal', 'recordMedication.selectedMedications'])
            ->where('log_id', $resultData['log_id'])
            ->first();

        return response()->json([
            'success' => true,
            'message' => $resultData['message'],
            'data' => $finalLog
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => $e->getMessage()
        ], 500);
    }
}

public function update(Request $request, Log $log)
{
    // 1. Validation
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
        'record_meal.total_calories' => 'nullable|numeric',
        'record_meal.total_carb' => 'nullable|numeric',
        'record_meal.notes' => 'nullable|string',

        'record_medication' => 'nullable|array',
        'record_medication.medications' => 'nullable|array',
        'record_medication.medications.*' => 'string',
        'record_medication.notes' => 'nullable|string',
    ]);

    try {
        // 2. Database Transaction
        $logId = DB::transaction(function () use ($validated, $log) {
            $userId = Auth::id();

            // Update the parent Log record
            $log->update([
                'log_title' => $validated['log_title'] ?? $log->log_title,
                'log_description' => $validated['log_description'] ?? $log->log_description,
            ]);

            // ─── Handle Glucose ───
            $glucoseData = $validated['record_glucose'] ?? null;
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

            // ─── Handle Meal ───
            $mealData = $validated['record_meal'] ?? null;
            if (!empty($mealData['meal_type'])) {
                Meal::updateOrCreate(
                    ['log_id' => $log->log_id],
                    [
                        'user_id' => $userId,
                        'meal_type' => $mealData['meal_type'],
                        'meal_description' => $mealData['meal_description'] ?? null,
                        'total_calories' => $mealData['total_calories'] ?? 0,
                        'total_carb' => $mealData['total_carb'] ?? 0,
                        'notes' => $mealData['notes'] ?? null,
                    ]
                );
            } else {
                Meal::where('log_id', $log->log_id)->delete();
            }

            // ─── Handle RecordMedication (البلدوزر) ───
            $medicationData = $validated['record_medication'] ?? null;
            if (!empty($medicationData)) {
                $recordMedication = RecordMedication::updateOrCreate(
                    ['log_id' => $log->log_id],
                    [
                        'user_id' => $userId,
                        'notes' => $medicationData['notes'] ?? null,
                    ]
                );

                // فك وتنظيف
                \App\Models\SelectedMedication::where('medication_id', $recordMedication->medication_id)->delete();

                // ربط جديد بالاسم
                if (!empty($medicationData['medications']) && is_array($medicationData['medications'])) {
                    foreach ($medicationData['medications'] as $medName) {
                        \App\Models\SelectedMedication::create([
                            'medication_id'   => $recordMedication->medication_id,
                            'medication_name' => $medName,
                            'user_id'         => $userId
                        ]);
                    }
                }
            } else {
                $recordMedication = RecordMedication::where('log_id', $log->log_id)->first();
                if ($recordMedication) {
                    \App\Models\SelectedMedication::where('medication_id', $recordMedication->medication_id)->delete();
                    $recordMedication->delete();
                }
            }

            return $log->log_id;
    });

        // 👈 جلب الداتا فريش برة الـ Transaction لضمان القراءة المكتملة
        $finalLog = Log::with(['recordGlucose', 'recordMeal', 'recordMedication.selectedMedications'])->find($logId);

        return response()->json([
            'success' => true,
            'message' => 'Log updated successfully',
            'data' => $finalLog
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
                // Log sync deletion before permanently deleting
                DB::table('sync_deletions')->insert([
                    'user_id' => $log->user_id,
                    'log_id' => $log->log_id,
                    'deleted_at' => now()
                ]);

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
     */public function show(Request $request, $date) 
{
    try {
        $userId = Auth::id();
        
        // 👈 استخدمنا where عادية عشان تطابق التاريخ والوقت بالثانية بالملي زي ما جاي من الـ URL
        $log = Log::where('user_id', $userId)
            ->whereDate('logged_at', $date) 
            ->with(['recordGlucose', 'recordMeal', 'recordMedication.selectedMedications'])
            ->get();

        if (!$log) {
            return response()->json([
                'success' => false,
                'message' => "No log found for the date: " . $date,
                'data' => null
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => "Log retrieved successfully for date: " . $date,
            'data' => $log
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
            ->with(['recordGlucose', 'recordMeal', 'recordMedication.selectedMedications']);

        $deletedLogIds = [];

        if ($request->has('last_sync') && !empty($request->input('last_sync'))) {
            $lastSyncRaw = $request->input('last_sync'); // جاي مثلاً: 2026-05-18 04:18:48
            
            try {
                // التعديل السحري: بنفهمه إن الوقت ده بتوقيت مصر، وحوله للـ timezone بتاعة السيرفر (UTC مثلاً) عشان المقارنة في الداتابيز تكون عادلة
                $lastSync = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $lastSyncRaw, 'Africa/Cairo')
                    ->setTimezone(config('app.timezone'));
                
                $query->where('updated_at', '>', $lastSync);

                // Fetch deleted log IDs since last_sync
                $deletedLogIds = DB::table('sync_deletions')
                    ->where('user_id', $userId)
                    ->where('deleted_at', '>', $lastSync)
                    ->pluck('log_id')
                    ->toArray();

            } catch (\Exception $e) {
                // لو حصلت مشكلة في الـ parsing لأي سبب، يشتغل بالـ raw كـ fallback
                $query->where('updated_at', '>', $lastSyncRaw);

                $deletedLogIds = DB::table('sync_deletions')
                    ->where('user_id', $userId)
                    ->where('deleted_at', '>', $lastSyncRaw)
                    ->pluck('log_id')
                    ->toArray();
            }
        }

        $updatedLogs = $query->orderBy('updated_at', 'asc')->get();

        return response()->json([
            'success' => true,
            'message' => 'Sync data retrieved successfully',
            'data' => [
                'upserted_logs' => $updatedLogs,
                'deleted_log_ids' => $deletedLogIds
            ]
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => $e->getMessage()
        ], 500);
    }
}    public function getLogById($log_id)
    {
        try {
            $userId = Auth::id();

            $log = Log::where('user_id', $userId)
                ->where('log_id', $log_id)
                ->with(['recordGlucose', 'recordMeal', 'recordMedication.selectedMedications']) ->first();
                 

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