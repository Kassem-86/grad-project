<?php

namespace App\Http\Controllers;

use App\Models\Meal;
use App\Models\Log;
use App\Models\RecordMealFoodItem;
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
            'meal_time' => 'required|date_format:Y-m-d H:i:s',
            'total_carb' => 'required|numeric|min:0',
            'total_calories' => 'required|numeric|min:0',
            'meal_type' => 'required|in:Breakfast,Lunch,Dinner,Snack',
            'notes' => 'nullable|string',
            'food_items' => 'required|array|min:1',
            'food_items.*.food_name' => 'required|string',
            'food_items.*.quantity' => 'required|numeric|min:0',
            'food_items.*.carbs' => 'required|numeric|min:0',
            'food_items.*.protein' => 'required|numeric|min:0',
            'food_items.*.fats' => 'required|numeric|min:0',
            'food_items.*.calories' => 'required|numeric|min:0',
        ]);

        try {
            $meal = DB::transaction(function () use ($validated) {
                // Create log entry first
                $log = Log::create([
                    'user_id' => Auth::id(),
                ]);

                // Create meal record linked to the log
                $meal = Meal::create([
                    'log_id' => $log->log_id,
                    'meal_time' => $validated['meal_time'],
                    'total_carb' => $validated['total_carb'],
                    'total_calories' => $validated['total_calories'],
                    'meal_type' => $validated['meal_type'],
                    'notes' => $validated['notes'] ?? null,
                ]);

                // Create associated food items
                foreach ($validated['food_items'] as $item) {
                    RecordMealFoodItem::create([
                        'meal_id' => $meal->meal_id,
                        'food_name' => $item['food_name'],
                        'quantity' => $item['quantity'],
                        'carbs' => $item['carbs'],
                        'protein' => $item['protein'],
                        'fats' => $item['fats'],
                        'calories' => $item['calories'],
                    ]);
                }

                return $meal->load('foodItems', 'log');
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
        $meals = Meal::whereHas('log', function ($query) {
            $query->where('user_id', Auth::id());
        })
            ->with('foodItems')
            ->orderBy('created_at', 'desc')
            ->get();

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
            'data' => $meal->load('foodItems', 'log'),
        ]);
    }

    /**
     * Update a meal record.
     */
    public function update(Request $request, Meal $meal): JsonResponse
    {
        $this->authorize('update', $meal);

        $validated = $request->validate([
            'meal_time' => 'sometimes|date_format:Y-m-d H:i:s',
            'total_carb' => 'sometimes|numeric|min:0',
            'total_calories' => 'sometimes|numeric|min:0',
            'meal_type' => 'sometimes|in:Breakfast,Lunch,Dinner,Snack',
            'notes' => 'sometimes|string|nullable',
            'food_items' => 'sometimes|array',
            'food_items.*.food_name' => 'required_with:food_items|string',
            'food_items.*.quantity' => 'required_with:food_items|numeric|min:0',
            'food_items.*.carbs' => 'required_with:food_items|numeric|min:0',
            'food_items.*.protein' => 'required_with:food_items|numeric|min:0',
            'food_items.*.fats' => 'required_with:food_items|numeric|min:0',
            'food_items.*.calories' => 'required_with:food_items|numeric|min:0',
        ]);

        try {
            $meal = DB::transaction(function () use ($meal, $validated) {
                // Update meal details
                $mealData = array_filter($validated, fn($key) => !in_array($key, ['food_items']), ARRAY_FILTER_USE_KEY);
                $meal->update($mealData);

                // Update food items if provided
                if (isset($validated['food_items'])) {
                    // Delete existing food items
                    $meal->foodItems()->delete();

                    // Create new food items
                    foreach ($validated['food_items'] as $item) {
                        RecordMealFoodItem::create([
                            'meal_id' => $meal->meal_id,
                            'food_name' => $item['food_name'],
                            'quantity' => $item['quantity'],
                            'carbs' => $item['carbs'],
                            'protein' => $item['protein'],
                            'fats' => $item['fats'],
                            'calories' => $item['calories'],
                        ]);
                    }
                }

                return $meal->load('foodItems');
            });

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

        DB::transaction(function () use ($meal) {
            $meal->foodItems()->delete();
            $log = $meal->log;
            $meal->delete();
            $log->delete();
        });

        return response()->json([
            'success' => true,
            'message' => 'Meal record deleted successfully',
        ]);
    }
}
