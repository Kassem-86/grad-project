<?php

namespace App\Http\Controllers;

use App\Models\SelectedMedication;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SelectedMedicationController extends Controller
{
    /**
     * Get all selected medications for the authenticated user.
     */
    public function index(): JsonResponse
    {
        $medications = SelectedMedication::where('user_id', Auth::id())
            ->orderByDesc('created_at')
            ->get()
            ->load('recordMedication', 'log');

        return response()->json([
            'success' => true,
            'data' => $medications,
        ]);
    }

    /**
     * Get a specific selected medication.
     */
    public function show(SelectedMedication $selectedMedication): JsonResponse
    {
        $this->authorize('view', $selectedMedication);

        return response()->json([
            'success' => true,
            'data' => $selectedMedication->load('recordMedication', 'log'),
        ]);
    }

    /**
     * Update a selected medication.
     */
    public function update(Request $request, SelectedMedication $selectedMedication): JsonResponse
    {
        $this->authorize('update', $selectedMedication);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:100',
            'dosage' => 'sometimes|string|max:50',
            'frequency' => 'sometimes|in:Once Daily,Twice Daily,Thrice Daily,As Needed',
            'notes' => 'sometimes|nullable|string',
        ]);

        try {
            $selectedMedication->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Medication updated successfully',
                'data' => $selectedMedication,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating medication',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete a selected medication.
     */
    public function destroy(SelectedMedication $selectedMedication): JsonResponse
    {
        $this->authorize('delete', $selectedMedication);

        try {
            $selectedMedication->delete();

            return response()->json([
                'success' => true,
                'message' => 'Medication deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting medication',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
