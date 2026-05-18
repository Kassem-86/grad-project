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
            ->load('recordMedication');

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
            'data' => $selectedMedication->load('recordMedication'),
        ]);
    }

    /**
     * Update a selected medication.
     */
    public function update(Request $request, SelectedMedication $selectedMedication): JsonResponse
    {
        $this->authorize('update', $selectedMedication);

        $validated = $request->validate([
            'medication_name' => 'sometimes|string|max:100',
   
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



    /**
 * Store a newly created selected medication.
 *//**
 * Store a newly created custom medication from the Add Box.
 */public function store(Request $request): JsonResponse
{
    // 1. Validation مبدئي على الاسم
    $validated = $request->validate([
        'medication_name' => 'required|string|max:50',
    ]);

    try {
        $userId = Auth::id();
        
        // تنظيف الاسم من المسافات الزيادة عشان الـ مقارنة تكون دقيقة
        $medicationName = trim($validated['medication_name']);

        // 2. التشيك السحري: هل الدوا ده متسجل عند نفس اليوزر قبل كده؟
        $exists = SelectedMedication::where('user_id', $userId)
            ->where('medication_name', $medicationName)
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'This medication already exists in your list.',
            ], 400); // 400 يعني Bad Request لأن الداتا مكررة
        }

        // 3. لو مش مكرر بيعمل Create عادي جداً
        $selectedMedication = SelectedMedication::create([
            'user_id'         => $userId,
            'medication_name' => $medicationName,
            'medication_id'   => null, 
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Medication created and added to your list successfully',
            'data'    => $selectedMedication,
        ], 201);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error creating medication',
            'error'   => $e->getMessage(),
        ], 500);
    }
}
}
