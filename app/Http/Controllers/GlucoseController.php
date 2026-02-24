<?php

namespace App\Http\Controllers;

use App\Models\Glucose;
use App\Models\Log;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class GlucoseController extends Controller
{
    /**
     * Store a newly created glucose reading.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'glucose_level' => 'required|numeric|min:0',
            'reading_time' => 'required|date_format:Y-m-d H:i:s',
            'reading_type' => 'required|in:Fasting,Before Meal,After Meal,Random',
            'notes' => 'nullable|string',
            'a1c_estimation' => 'nullable|numeric|min:0',
            'average_glucose_level' => 'nullable|numeric|min:0',
        ]);

        try {
            $glucose = DB::transaction(function () use ($validated) {
                // Create log entry first
                $log = Log::create([
                    'user_id' => Auth::id(),
                ]);

                // Create glucose reading linked to the log
                $glucose = Glucose::create([
                    'log_id' => $log->log_id,
                    'glucose_level' => $validated['glucose_level'],
                    'reading_time' => $validated['reading_time'],
                    'reading_type' => $validated['reading_type'],
                    'notes' => $validated['notes'] ?? null,
                    'a1c_estimation' => $validated['a1c_estimation'] ?? null,
                    'average_glucose_level' => $validated['average_glucose_level'] ?? null,
                ]);

                return $glucose->load('log');
            });

            return response()->json([
                'success' => true,
                'message' => 'Glucose reading recorded successfully',
                'data' => $glucose,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error recording glucose reading',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get all glucose readings for the authenticated user.
     */
    public function index(): JsonResponse
    {
        $readings = Glucose::whereHas('log', function ($query) {
            $query->where('user_id', Auth::id());
        })
            ->orderBy('reading_time', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $readings,
        ]);
    }

    /**
     * Get a specific glucose reading.
     */
    public function show(Glucose $glucose): JsonResponse
    {
        $this->authorize('view', $glucose);

        return response()->json([
            'success' => true,
            'data' => $glucose->load('log'),
        ]);
    }

    /**
     * Update a glucose reading.
     */
    public function update(Request $request, Glucose $glucose): JsonResponse
    {
        $this->authorize('update', $glucose);

        $validated = $request->validate([
            'glucose_level' => 'sometimes|numeric|min:0',
            'reading_time' => 'sometimes|date_format:Y-m-d H:i:s',
            'reading_type' => 'sometimes|in:Fasting,Before Meal,After Meal,Random',
            'notes' => 'sometimes|string|nullable',
            'a1c_estimation' => 'sometimes|numeric|min:0|nullable',
            'average_glucose_level' => 'sometimes|numeric|min:0|nullable',
        ]);

        $glucose->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Glucose reading updated successfully',
            'data' => $glucose,
        ]);
    }

    /**
     * Delete a glucose reading.
     */
    public function destroy(Glucose $glucose): JsonResponse
    {
        $this->authorize('delete', $glucose);

        DB::transaction(function () use ($glucose) {
            $log = $glucose->log;
            $glucose->delete();
            $log->delete();
        });

        return response()->json([
            'success' => true,
            'message' => 'Glucose reading deleted successfully',
        ]);
    }
}
