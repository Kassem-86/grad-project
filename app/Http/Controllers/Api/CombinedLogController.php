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
     */public function store(Request $request)
    {
        // 1. Validation (تعديل الـ Keys لـ snake_case والـ IDs)
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
            'record_medication.selected_medication_ids' => 'nullable|array',
            'record_medication.selected_medication_ids.*' => 'integer|exists:selected_medications,selected_med_id',
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
                    'user_id'         => $userId,
                    'log_title'       => $validated['log_title'] ?? null,
                    'log_description' => $validated['log_description'] ?? null,
                    'logged_at'       => $loggedAt,
                ]);

                // Create Glucose record
                $glucoseData = $request->input('record_glucose');
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
                $mealData = $request->input('record_meal');
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

                // Handle RecordMedication and linking SelectedMedications
                $medicationData = $request->input('record_medication');
                if (!empty($medicationData['selected_medication_ids']) && is_array($medicationData['selected_medication_ids'])) {
                    // أ) كريت الأب الأول
                    $recordMedication = RecordMedication::create([
                        'log_id'  => $log->log_id,
                        'user_id' => $userId,
                        'notes'   => $medicationData['notes'] ?? null,
                    ]);

                    // ب) التعديل السحري: نعمل تحديث للأدوية اللي المريض اختارها عشان نربطها بالـ Log والـ Record الحالي
                    \App\Models\SelectedMedication::whereIn('selected_med_id', $medicationData['selected_medication_ids'])
                        ->update([
                            'medication_id' => $recordMedication->medication_id
                        ]);
                }

                // بنرجع الـ log وبنعمل load لـ لستة الأدوية اللي جوة الـ recordMedication عشان تبان للفرونت إند
                return $log->load(['recordGlucose', 'recordMeal', 'recordMedication.selectedMedications']);
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
     */public function storeWithAndroidId(Request $request)
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
        'record_medication.selected_medication_ids' => 'nullable|array',
        'record_medication.selected_medication_ids.*' => 'integer|exists:selected_medications,selected_med_id',
        'record_medication.notes' => 'nullable|string',
    ]);

    try {
        $result = DB::transaction(function () use ($request, $validated) {
            $userId = Auth::id();
            $logId = $validated['log_id'];
            $loggedAt = !empty($validated['logged_at']) ? $validated['logged_at'] : now();

            // ─── ON CONFLICT STRATEGY (UPSERT) ───
            // بنشوف اللوج موجود قبل كده ولا لأ
            $log = Log::where('log_id', $logId)->where('user_id', $userId)->first();

            if ($log) {
                // لو موجود: بنعمل Update (الـ Android جاي يـعدل أو بيبعت داتا قديمة)
                $log->update([
                    'log_title' => $validated['log_title'] ?? $log->log_title,
                    'log_description' => $validated['log_description'] ?? $log->log_description,
                    'logged_at' => $loggedAt,
                ]);
                $message = 'Log updated successfully on conflict';
            } else {
                // لو مش موجود: بنعمل Insert عادي جداً
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
                // باستخدام updateOrCreate: لو السطر موجود بالـ log_id ده هيحدثه، لو مش موجود هيكريته
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
                // لو المريض مسح قراءة السكر من الموبايل في التعديل، بنمسحها من السيرفر
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

            // ─── 3. MEDICATION UPSERT (السيناريو بتاعك بدون log_id جوه الأدوية) ───
            $medicationData = $request->input('record_medication');
            
            // أولاً: بنظبط السطر الأب (RecordMedication) بـ updateOrCreate
            $recordMedication = RecordMedication::updateOrCreate(
                ['log_id' => $logId, 'user_id' => $userId],
                ['notes' => $medicationData['notes'] ?? null]
            );

            // ثانياً: فك الارتباط القديم لأي دوا مربوط بالـ medication_id ده (عشان لو اليوزر غير رأيه في الأندرويد)
            \App\Models\SelectedMedication::where('medication_id', $recordMedication->medication_id)
                ->update(['medication_id' => null]);

            // ثالثاً: ربط الأدوية الجديدة المبعوتة حالا في الـ Array
            if (!empty($medicationData['selected_medication_ids']) && is_array($medicationData['selected_medication_ids'])) {
                \App\Models\SelectedMedication::whereIn('selected_med_id', $medicationData['selected_medication_ids'])
                    ->update([
                        'medication_id' => $recordMedication->medication_id
                    ]);
            }

            return [
                'log' => $log->load(['recordGlucose', 'recordMeal', 'recordMedication.selectedMedications']),
                'message' => $message
            ];
        });

        return response()->json([
            'success' => true,
            'message' => $result['message'],
            'data' => $result['log']
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

        if ($request->has('last_sync') && !empty($request->input('last_sync'))) {
            $lastSyncRaw = $request->input('last_sync'); // جاي مثلاً: 2026-05-18 04:18:48
            
            try {
                // التعديل السحري: بنفهمه إن الوقت ده بتوقيت مصر، وحوله للـ timezone بتاعة السيرفر (UTC مثلاً) عشان المقارنة في الداتابيز تكون عادلة
                $lastSync = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $lastSyncRaw, 'Africa/Cairo')
                    ->setTimezone(config('app.timezone'));
                
                $query->where('updated_at', '>', $lastSync);
            } catch (\Exception $e) {
                // لو حصلت مشكلة في الـ parsing لأي سبب، يشتغل بالـ raw كـ fallback
                $query->where('updated_at', '>', $lastSyncRaw);
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
}    public function getLogById($log_id)
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