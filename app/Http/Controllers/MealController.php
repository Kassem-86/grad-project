<?php

namespace App\Http\Controllers;

use App\Models\Meal;
use App\Models\Log;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class MealController extends Controller
{
    /**
     * Store a newly created meal record.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'total_carb' => 'required|numeric|min:0',
            'total_calories' => 'required|numeric|min:0',
            'meal_type' => 'required|in:Breakfast,Lunch,Dinner,Snack',
            'meal_description' => 'nullable|string|max:50',
            'notes' => 'nullable|string',
        ]);

        try {
            $meal = DB::transaction(function () use ($validated) {
                $log = Log::create([
                    'user_id' => Auth::id(),
                ]);

                $meal = Meal::create([
                    'log_id' => $log->log_id,
                    'user_id' => Auth::id(),
                    'total_carb' => $validated['total_carb'],
                    'total_calories' => $validated['total_calories'],
                    'meal_type' => $validated['meal_type'],
                    'meal_description' => $validated['meal_description'] ?? null,
                    'notes' => $validated['notes'] ?? null,
                ]);

                return $meal->load('log');
            });

            return response()->json([
                'success' => true,
                'message' => 'Meal record created successfully',
                'data' => $meal,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating meal record',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get all meals for the authenticated user.
     */
    public function index(): JsonResponse
    {
        $meals = Meal::where('user_id', Auth::id())
            ->orderByDesc('created_at')
            ->get()
            ->load('log');

        return response()->json([
            'success' => true,
            'data' => $meals,
        ]);
    }

    /**
     * Get a specific meal record.
     */
    public function show(Meal $meal): JsonResponse
    {
        $this->authorize('view', $meal);

        return response()->json([
            'success' => true,
            'data' => $meal->load('log'),
        ]);
    }

    /**
     * Update a meal record.
     */
    public function update(Request $request, Meal $meal): JsonResponse
    {
        $this->authorize('update', $meal);

        $validated = $request->validate([
            'total_carb' => 'sometimes|numeric|min:0|nullable',
            'total_calories' => ' sometimes|numeric|min:0 |nullable',
            'meal_type' => 'sometimes|in:Breakfast,Lunch,Dinner,Snack|nullable',
            'meal_description' => 'sometimes|string|max:50|nullable|nullable',
            'notes' => 'sometimes|string|nullable',
        ]);

        try {
            $meal->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Meal record updated successfully',
                'data' => $meal,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating meal record',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete a meal record.
     */
    public function destroy(Meal $meal): JsonResponse
    {
        $this->authorize('delete', $meal);

        try {
            DB::transaction(function () use ($meal) {
                $log = $meal->log;
                $meal->delete();
                if ($log) {
                    $log->delete();
                }
            });

            return response()->json([
                'success' => true,
                'message' => 'Meal record deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting meal record',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}

