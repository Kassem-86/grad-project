<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Chatbot;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ChatbotController extends Controller
{
    public function askChatbot(Request $request)
    {
        // 1. الـ Validation طالب الرسالة بس
        $request->validate([
            'message' => 'required|string',
        ]);

       $user = auth('sanctum')->user();

        if (!$user) {
            return response()->json(['error' => 'Unauthenticated.'], 401);
        }

        // 🔄 الخطوة 2 بقيت (سحب الـ History الأول): سحب آخر 12 رسالة قديمة "قبل" ما نسيف الجديدة
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

        // 🔄 الخطوة 3 بقيت (حفظ الرسالة الحالية): بنسيفها بعد ما خدنا الـ History خلاص عشان متظهرش فيه مرتين
        Chatbot::create([
            'user_id' => $user->id,
            'role'    => 'user',
            'content' => $request->message,
        ]);        

        // -------------------------------------------------------------
        // 🔥 تجميع الـ PayloadMedical من قاعدة البيانات عندك تلقائياً 🔥
        // -------------------------------------------------------------

        // -------------------------------------------------------------
        // 🔥 تجميع الـ PayloadMedical من قاعدة البيانات عندك تلقائياً 🔥
        // -------------------------------------------------------------

        // أ) تجميع الـ user_profile
        // حساب متوسط السكر لآخر 7 أيام
        $avgGlucose7d = \DB::table('record_glucose') 
            ->join('logs', 'record_glucose.log_id', '=', 'logs.log_id')
            ->where('record_glucose.user_id', $user->id)
            ->where('logs.logged_at', '>=', Carbon::now()->subDays(7))
            ->avg('record_glucose.glucose_level');

        // جلب آخر قراءة سكر مدخلة
        $lastGlucoseLog = \DB::table('record_glucose')
            ->join('logs', 'record_glucose.log_id', '=', 'logs.log_id')
            ->where('record_glucose.user_id', $user->id)
            ->latest('logs.logged_at')
            ->first();

        // 🔥 ديناميكية حساب السن: بيشيك على birth_date أو date_of_birth أوتوماتيك
        $birthDateColumn = $user->birth_date ?? $user->date_of_birth ?? null;
        $userAge = $birthDateColumn ? Carbon::parse($birthDateColumn)->age : 45; // 45 قيمة افتراضية لو مش مدخل تاريخ ميلاده

        $userProfile = [
            'full_name'          => trim($user->first_name . ' ' . $user->last_name),
            'diabetes_type'      => $user->diabetes_type, 
            'age'                => $userAge, // 🔥 هيتبعت رقم صحيح جاهز للـ AI (مثلاً: 24)
            'gender'             => $user->gender,
            'weight'             => $user->weight ? $user->weight . ' kg' : 'N/A',
            'height'             => $user->height ? $user->height . ' cm' : 'N/A',
            'avg_glucose_7d'     => $avgGlucose7d ? round($avgGlucose7d, 1) . ' mg/dL' : 'N/A',
            'last_glucose_level' => $lastGlucoseLog ? $lastGlucoseLog->glucose_level . ' mg/dL' : 'N/A',
            'estimated_a1c'      => $user->estimated_a1c ?? 'N/A',
        ];

        // ب) تجميع الـ user_medications (الأدوية اللي اليوزر ضايفها لنفسه)
        $userMedications = \DB::table('selected_medications')
            ->where('user_id', $user->id)
            ->pluck('medication_name')
            ->toArray();

        // جـ) تجميع الـ last_log (آخر سجل بالكامل اليوزر دخله)
        $lastLogData = \DB::table('logs')
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
                'date'                => Carbon::parse($lastLogData->logged_at)->toDateString(),
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

        // د) تجميع الـ nearest_reminder (أقرب تذكير قادم في المستقبل)
        $nextReminder = \DB::table('reminders')
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
                'date'       => Carbon::parse($nextReminder->time)->format('Y-m-d H:i'),
            ];
        }

        // 4. بناء الـ Payload النهائي المتكامل اللي هيروح للـ AI
        $payload = [
            'message'          => $request->message,
            'history'          => $historyData,
            'user_profile'     => $userProfile,
            'user_medications' => $userMedications,
            'last_log'         => $lastLog,
            'nearest_reminder' => $nearestReminder,
        ];

        // 5. إرسال الـ Payload لسيرفر الـ AI
        try {
           // 🚀 زودنا ->timeout(120) عشان نسيب الـ AI براحته وميفصلش
$response = Http::timeout(190)->withHeaders([
    'ngrok-skip-browser-warning' => 'true'
])->post('https://danger-tartness-font.ngrok-free.dev/chat', $payload);
            if ($response->successful()) {
                $responseData = $response->json();

                // 6. حفظ رد الـ AI في قاعدة البيانات كـ assistant
                if (isset($responseData['answer'])) {
                    Chatbot::create([
                        'user_id' => $user->id,
                        'role'    => 'assistant',
                        'content' => $responseData['answer'],
                    ]);
                }

                // 7. إرجاع الرد النهائي النظيف للأندرويد
                return response()->json($responseData, 200);
            } else {
                Log::error('AI Server Error: ', ['status' => $response->status(), 'body' => $response->body()]);
                return response()->json(['error' => 'Failed to connect to AI server.'], 502);
            }
        } catch (\Exception $e) {
            Log::error('AI Server Exception: ', ['exception' => $e->getMessage()]);
            return response()->json(['error' => 'An error occurred while contacting AI server.'], 500);
        }
    }


    public function getChatHistory()
{
    $user = auth('sanctum')->user();

    if (!$user) {
        return response()->json(['error' => 'Unauthenticated.'], 401);
    }

    // سحب كل رسائل الشات الخاصة باليوزر مترتبة من الأقدم للأحدث عشان تتعرض صح في الشاشة
    $chatHistory = Chatbot::where('user_id', $user->id)
        ->orderBy('created_at', 'asc') // من الأقدم للأحدث عشان الـ UI يرصهم صح
        ->get(['role', 'content', 'created_at']); // سحبنا الحقول المهمة بس

    return response()->json([
        'success' => true,
        'history' => $chatHistory
    ], 200);
}
}