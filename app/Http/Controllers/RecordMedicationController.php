<?php

namespace App\Http\Controllers;

use App\Models\RecordMedication;
use App\Models\Log;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class RecordMedicationController extends Controller
{
    /**
     * Store a newly created medication record.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'medications' => 'required|array',
            'medications.*.name' => 'required|string|max:100',
            'medications.*.dosage' => 'required|string|max:50',
            'medications.*.frequency' => 'required|in:Once Daily,Twice Daily,Thrice Daily,As Needed',
            'medications.*.notes' => 'nullable|string',
        ]);

        try {
            $recordMedication = DB::transaction(function () use ($validated) {
                $log = Log::create([
                    'user_id' => Auth::id(),
                ]);

                $record = RecordMedication::create([
                    'medication_id' => $log->log_id,
                    'user_id' => Auth::id(),
                    'medications' => $validated['medications'],
                ]);

                return $record->load('log');
            });

            return response()->json([
                'success' => true,
                'message' => 'Medication record created successfully',
                'data' => $recordMedication,
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
     * Get all medication records for the authenticated user.
     */
    public function index(): JsonResponse
    {
        $medications = RecordMedication::where('user_id', Auth::id())
            ->orderByDesc('created_at')
            ->get()
            ->load('log');

        return response()->json([
            'success' => true,
            'data' => $medications,
        ]);
    }

    /**
     * Get a specific medication record.
     */
    public function show(RecordMedication $recordMedication): JsonResponse
    {
        $this->authorize('view', $recordMedication);

        return response()->json([
            'success' => true,
            'data' => $recordMedication->load('log'),
        ]);
    }

    /**
     * Update a medication record.
     */
    public function update(Request $request, RecordMedication $recordMedication): JsonResponse
    {
        $this->authorize('update', $recordMedication);

        $validated = $request->validate([
            'medications' => 'sometimes|array',
            'medications.*.name' => 'required_with:medications|string|max:100',
            'medications.*.dosage' => 'required_with:medications|string|max:50',
            'medications.*.frequency' => 'required_with:medications|in:Once Daily,Twice Daily,Thrice Daily,As Needed',
            'medications.*.notes' => 'nullable|string',
        ]);

        try {
            $recordMedication->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Medication record updated successfully',
                'data' => $recordMedication,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating medication record',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete a medication record.
     */
    public function destroy(RecordMedication $recordMedication): JsonResponse
    {
        $this->authorize('delete', $recordMedication);

        try {
            DB::transaction(function () use ($recordMedication) {
                $log = $recordMedication->log;
                $recordMedication->delete();
                if ($log) {
                    $log->delete();
                }
            });

            return response()->json([
                'success' => true,
                'message' => 'Medication record deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting medication record',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
