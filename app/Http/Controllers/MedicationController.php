<?php

namespace App\Http\Controllers;

use App\Models\Medication;
use App\Models\Log;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class MedicationController extends Controller
{
    /**
     * Store a newly created medication record.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'medication_name' => 'required|string|max:255',
            'dosage' => 'required|string',
            'route' => 'required|in:Oral,Injection,Inhaler,Topical,IV',
            'unit' => 'required|string',
            'frequency' => 'required|string',
            'start_date' => 'required|date_format:Y-m-d',
            'end_date' => 'nullable|date_format:Y-m-d',
            'reminder_time' => 'nullable|date_format:H:i:s',
            'notes' => 'nullable|string',
        ]);

        try {
            $medication = DB::transaction(function () use ($validated) {
                // Create log entry first
                $log = Log::create([
                    'user_id' => Auth::id(),
                ]);

                // Create medication record linked to the log
                $medication = Medication::create([
                    'log_id' => $log->log_id,
                    'medication_name' => $validated['medication_name'],
                    'dosage' => $validated['dosage'],
                    'route' => $validated['route'],
                    'unit' => $validated['unit'],
                    'frequency' => $validated['frequency'],
                    'start_date' => $validated['start_date'],
                    'end_date' => $validated['end_date'] ?? null,
                    'reminder_time' => $validated['reminder_time'] ?? null,
                    'notes' => $validated['notes'] ?? null,
                ]);

                return $medication->load('log');
            });

            return response()->json([
                'success' => true,
                'message' => 'Medication record created successfully',
                'data' => $medication,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating medication record',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get all medications for the authenticated user.
     */
    public function index(): JsonResponse
    {
        $medications = Medication::whereHas('log', function ($query) {
            $query->where('user_id', Auth::id());
        })
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $medications,
        ]);
    }

    /**
     * Get a specific medication record.
     */
    public function show(Medication $medication): JsonResponse
    {
        $this->authorize('view', $medication);

        return response()->json([
            'success' => true,
            'data' => $medication->load('log'),
        ]);
    }

    /**
     * Update a medication record.
     */
    public function update(Request $request, Medication $medication): JsonResponse
    {
        $this->authorize('update', $medication);

        $validated = $request->validate([
            'medication_name' => 'sometimes|string|max:255',
            'dosage' => 'sometimes|string',
            'route' => 'sometimes|in:Oral,Injection,Inhaler,Topical,IV',
            'unit' => 'sometimes|string',
            'frequency' => 'sometimes|string',
            'start_date' => 'sometimes|date_format:Y-m-d',
            'end_date' => 'sometimes|date_format:Y-m-d|nullable',
            'reminder_time' => 'sometimes|date_format:H:i:s|nullable',
            'notes' => 'sometimes|string|nullable',
        ]);

        $medication->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Medication record updated successfully',
            'data' => $medication,
        ]);
    }

    /**
     * Delete a medication record.
     */
    public function destroy(Medication $medication): JsonResponse
    {
        $this->authorize('delete', $medication);

        DB::transaction(function () use ($medication) {
            $log = $medication->log;
            $medication->delete();
            $log->delete();
        });

        return response()->json([
            'success' => true,
            'message' => 'Medication record deleted successfully',
        ]);
    }
}
