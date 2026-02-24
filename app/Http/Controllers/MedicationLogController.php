<?php

namespace App\Http\Controllers;

use App\Models\MedicationLog;
use App\Models\Medication;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MedicationLogController extends Controller
{
    /**
     * Store a new medication log (track medication status).
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'medication_id' => 'required|integer|exists:medications,medication_id',
            'taken_at' => 'required|date_format:Y-m-d H:i:s',
            'status' => 'required|in:taken,missed,skipped',
        ]);

        try {
            // Check if the medication belongs to the authenticated user
            $medication = Medication::with('log')->findOrFail($validated['medication_id']);
            
            if ($medication->log->user_id !== Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access to this medication',
                ], 403);
            }

            $log = MedicationLog::create([
                'medication_id' => $validated['medication_id'],
                'taken_at' => $validated['taken_at'],
                'status' => $validated['status'],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Medication log recorded successfully',
                'data' => $log->load('medication'),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error recording medication log',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get all medication logs for a specific medication.
     */
    public function index(Request $request): JsonResponse
    {
        $medicationId = $request->query('medication_id');

        if (!$medicationId) {
            return response()->json([
                'success' => false,
                'message' => 'medication_id parameter is required',
            ], 400);
        }

        try {
            $medication = Medication::with('log')->findOrFail($medicationId);
            
            if ($medication->log->user_id !== Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access to this medication',
                ], 403);
            }

            $logs = MedicationLog::where('medication_id', $medicationId)
                ->orderBy('taken_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $logs,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving medication logs',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update a medication log status.
     */
    public function update(Request $request, MedicationLog $medicationLog): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:taken,missed,skipped',
        ]);

        try {
            $medication = $medicationLog->medication;
            
            if ($medication->log->user_id !== Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access to this medication log',
                ], 403);
            }

            $medicationLog->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Medication log updated successfully',
                'data' => $medicationLog,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating medication log',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete a medication log.
     */
    public function destroy(MedicationLog $medicationLog): JsonResponse
    {
        try {
            $medication = $medicationLog->medication;
            
            if ($medication->log->user_id !== Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access to this medication log',
                ], 403);
            }

            $medicationLog->delete();

            return response()->json([
                'success' => true,
                'message' => 'Medication log deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting medication log',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
