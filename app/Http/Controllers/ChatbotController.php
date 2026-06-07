<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Chatbot;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ChatbotController extends Controller
{
    public function askChatbot(Request $request)
    {
        // 1. Validation
        $request->validate([
            'message' => 'required|string',
        ]);

        $user = auth('sanctum')->user();
        if (!$user) {
            return response()->json(['error' => 'Unauthenticated.'], 401);
        }

        // 2. سحب الـ History (قراءة فقط)
        $historyData = Chatbot::where('user_id', $user->id)
            ->latest()
            ->take(12)
            ->get()
            ->reverse()
            ->map(function ($chat) {
                return [
                    'role'    => $chat->role,
                    'content' => $chat->content,
                ];
            })->values()->toArray();

        // 3. تجميع الـ Payload (الـ Profile, Medications, Logs)
        // أ) تجميع الـ user_profile
        $avgGlucose7d = DB::table('record_glucose') 
            ->join('logs', 'record_glucose.log_id', '=', 'logs.log_id')
            ->where('record_glucose.user_id', $user->id)
            ->where('logs.logged_at', '>=', Carbon::now()->subDays(7))
            ->avg('record_glucose.glucose_level');

        $lastGlucoseLog = DB::table('record_glucose')
            ->join('logs', 'record_glucose.log_id', '=', 'logs.log_id')
            ->where('record_glucose.user_id', $user->id)
            ->latest('logs.logged_at')
            ->first();

        $birthDateColumn = $user->birth_date ?? $user->date_of_birth ?? null;
        $userAge = $birthDateColumn ? Carbon::parse($birthDateColumn)->age : 45;

        $userProfile = [
            'full_name'          => trim($user->first_name . ' ' . $user->last_name),
            'diabetes_type'      => $user->diabetes_type, 
            'age'                => $userAge,
            'gender'             => $user->gender,
            'weight'             => $user->weight ? $user->weight . ' kg' : 'N/A',
            'height'             => $user->height ? $user->height . ' cm' : 'N/A',
            'avg_glucose_7d'     => $avgGlucose7d ? round($avgGlucose7d, 1) . ' mg/dL' : 'N/A',
            'last_glucose_level' => $lastGlucoseLog ? $lastGlucoseLog->glucose_level . ' mg/dL' : 'N/A',
            'estimated_a1c'      => $user->estimated_a1c ?? 'N/A',
        ];

        $userMedications = DB::table('selected_medications')
            ->where('user_id', $user->id)
            ->pluck('medication_name')
            ->toArray();

        $lastLogData = DB::table('logs')
            ->leftJoin('record_glucose', 'logs.log_id', '=', 'record_glucose.log_id')
            ->leftJoin('record_medications', 'logs.log_id', '=', 'record_medications.log_id')
            ->leftJoin('record_meals', 'logs.log_id', '=', 'record_meals.log_id')
            ->where('logs.user_id', $user->id)
            ->latest('logs.logged_at')
            ->first();

        $lastLog = null;
        if ($lastLogData) {
            $lastLog = [
                'title'               => $lastLogData->log_title ?? 'User Log',
                'description'         => $lastLogData->log_description ?? '',
                'logged_at'           => Carbon::parse($lastLogData->logged_at)->format('Y-m-d h:i A'),
                'glucose_level'       => isset($lastLogData->glucose_level) ? $lastLogData->glucose_level . ' mg/dL' : 'N/A',
                'reading_type'        => $lastLogData->reading_type ?? 'N/A',
                'glucose_notes'       => $lastLogData->notes ?? '', 
                'medications_taken'   => isset($lastLogData->medications) ? json_decode($lastLogData->medications) : [], 
                'medication_notes'    => $lastLogData->medication_notes ?? '',
                'meal_description'    => $lastLogData->meal_description ?? '',
                'meal_type'           => $lastLogData->meal_type ?? '',
                'meal_carbs_estimate' => $lastLogData->carbs ?? '', 
                'meal_calories'       => $lastLogData->calories ?? '',
                'meal_notes'          => $lastLogData->meal_notes ?? '',
            ];
        }

        $nextReminder = DB::table('reminders')
            ->where('user_id', $user->id)
            ->where('time', '>=', Carbon::now())
            ->orderBy('time', 'asc')
            ->first();

        $nearestReminder = null;
        if ($nextReminder) {
            $nearestReminder = [
                'title'      => $nextReminder->title,
                'medication' => $nextReminder->medication_name ?? '',
                'type'       => $nextReminder->message_type ?? 'Medication',
                'date'       => Carbon::parse($nextReminder->time)->format('Y-m-d h:i A'),
            ];
        }

        $payload = [
            'message'          => $request->message,
            'history'          => $historyData,
            'user_profile'     => $userProfile,
            'user_medications' => $userMedications,
            'last_log'         => $lastLog,
            'nearest_reminder' => $nearestReminder,
        ];

        // 4. إرسال الطلب للـ AI
        try {
            $response = Http::timeout(190)->withHeaders([
                'ngrok-skip-browser-warning' => 'true'
            ])->post('https://danger-tartness-font.ngrok-free.dev/chat', $payload);

            if ($response->successful()) {
                $responseData = $response->json();

                // 5. حفظ الرسائل في الداتابيز فقط عند النجاح
                DB::transaction(function () use ($user, $request, $responseData) {
                    Chatbot::create([
                        'user_id' => $user->id,
                        'role'    => 'user',
                        'content' => $request->message,
                    ]);

                    if (isset($responseData['answer'])) {
                        Chatbot::create([
                            'user_id' => $user->id,
                            'role'    => 'assistant',
                            'content' => $responseData['answer'],
                        ]);
                    }
                });

                return response()->json($responseData, 200);
            }

            Log::error('AI Server Error: ', ['status' => $response->status(), 'body' => $response->body()]);
            return response()->json(['error' => 'Failed to connect to AI server.'], 502);

        } catch (\Exception $e) {
            Log::error('AI Server Exception: ', ['exception' => $e->getMessage()]);
            return response()->json(['error' => 'An error occurred while contacting AI server.'], 500);
        }
    }

    public function getChatHistory()
    {
        $user = auth('sanctum')->user();
        if (!$user) return response()->json(['error' => 'Unauthenticated.'], 401);

        $chatHistory = Chatbot::where('user_id', $user->id)
            ->orderBy('created_at', 'asc')
            ->get(['role', 'content', 'created_at']);

        return response()->json([
            'success' => true,
            'history' => $chatHistory
        ], 200);
    }
}