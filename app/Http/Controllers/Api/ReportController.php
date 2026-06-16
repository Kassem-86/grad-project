<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Glucose;
use Carbon\Carbon;
use Google\Type\TimeOfDay;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Barryvdh\DomPDF\Facade\Pdf; 

class ReportController extends Controller
{
    /**
     * 1️⃣ API to get raw JSON data for a specific date range
     */
    public function getGlucoseReportData(Request $request): JsonResponse
    {
        // ... (كود الـ JSON زي ما هو مفيش فيه تغيير)
        $user = $request->user();
        $request->validate([
            'start_date' => 'required|date|date_format:Y-m-d',
            'end_date'   => 'required|date|date_format:Y-m-d|after_or_equal:start_date',
        ]);

        $startDate = $request->query('start_date');
        $endDate   = $request->query('end_date');
        $start = Carbon::parse($startDate, 'Africa/Cairo')->startOfDay();
        $end   = Carbon::parse($endDate, 'Africa/Cairo')->endOfDay();

        $readings = Glucose::where('record_glucose.user_id', $user->id)
            ->join('logs', 'record_glucose.log_id', '=', 'logs.log_id')
            ->whereBetween('logs.logged_at', [$start, $end])
            ->orderBy('logs.logged_at', 'desc')
            ->select('record_glucose.*', 'logs.logged_at as log_logged_at')
            ->get();

        if ($readings->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'No data found.', 'data' => null], 404);
        }

        $avgGlucose = round($readings->avg('glucose_level'));
        $stats = [
            'total_readings'  => $readings->count(),
            'lowest_glucose'  => (int) $readings->min('glucose_level'),
            'highest_glucose' => (int) $readings->max('glucose_level'),
            'avg_glucose'     => $avgGlucose,
            'a1c_estimation'  => round(($avgGlucose + 46.7) / 28.7, 1),
        ];

        $formattedReadings = $readings->map(function($reading) {
$logTime = Carbon::parse($reading->log_logged_at, 'UTC')->setTimezone('Africa/Cairo');            return [
                'glucose_level' => (int) $reading->glucose_level,
                'reading_type'  => $reading->reading_type,
                'notes'         => $reading->notes,
                'date'          => $logTime->format('Y-m-d'),
                'time'          => $logTime->format('H:i A'),
            ];
        })->values();

        return response()->json([
            'success'    => true,
            'username'   => $user->first_name . ' ' . $user->last_name,
            'start_date' => $startDate,
            'end_date'   => $endDate,
            'stats'      => $stats,
            'readings'   => $formattedReadings
        ], 200);
    }

    /**
     * 2️⃣ API to download the English PDF Report with Logo
     */
    public function exportGlucosePdf(Request $request)
    {
        $user = $request->user();
        
        $request->validate([
            'start_date' => 'required|date|date_format:Y-m-d',
            'end_date'   => 'required|date|date_format:Y-m-d|after_or_equal:start_date',
        ]);

        $startDate = $request->query('start_date');
        $endDate   = $request->query('end_date');
        $start = Carbon::parse($startDate, 'Africa/Cairo')->startOfDay();
        $end   = Carbon::parse($endDate, 'Africa/Cairo')->endOfDay();
        
        $readings = Glucose::where('record_glucose.user_id', $user->id)
            ->join('logs', 'record_glucose.log_id', '=', 'logs.log_id')
            ->whereBetween('logs.logged_at', [$start, $end])
            ->orderBy('logs.logged_at', 'desc')
            ->select('record_glucose.*', 'logs.logged_at as log_logged_at')
            ->get();

        if ($readings->isEmpty()) {
            return response()->json(['message' => 'No data found to export PDF.'], 404);
        }

        // تحويل الصورة لـ Base64
        $path = public_path('images/logo.jpeg'); // تأكد إن الصورة هنا
        $type = pathinfo($path, PATHINFO_EXTENSION);
        $dataImg = file_get_contents($path);
        $base64 = 'data:image/' . $type . ';base64,' . base64_encode($dataImg);

        $avgGlucose = round($readings->avg('glucose_level'));
        $stats = [
            'lowest_glucose'  => (int) $readings->min('glucose_level'),
            'highest_glucose' => (int) $readings->max('glucose_level'),
            'avg_glucose'     => $avgGlucose,
            'a1c_estimation'  => round(($avgGlucose + 46.7) / 28.7, 1),
        ];

        $days = Carbon::parse($startDate)->diffInDays(Carbon::parse($endDate)) + 1;

        $data = [
            'username'   => $user->first_name . ' ' . $user->last_name,
            'days'       => $days,
            'start_date' => $startDate,
            'end_date'   => $endDate,
            'stats'      => $stats,
            'readings'   => $readings,
            'base64'     => $base64 // بعتنا الصورة للـ View
        ];

        $pdf = Pdf::loadView('reports.glucose_pdf', $data)
                  ->setOption([
                      'defaultFont' => 'Arial',
                      'isHtml5ParserEnabled' => true,
                      'isRemoteEnabled' => true // مهم جداً عشان الصور
                  ]);

        return $pdf->download('seen_report_' . $startDate . '.pdf');
    }
public function getGlucoseReportGraph(Request $request): JsonResponse
{
    $user = $request->user();

    // 1. التحقق من التواريخ
    $request->validate([
        'start_date' => 'required|date|date_format:Y-m-d',
        'end_date'   => 'required|date|date_format:Y-m-d|after_or_equal:start_date',
    ]);

    $startDate = Carbon::parse($request->query('start_date'), 'Africa/Cairo')->startOfDay();
    $endDate   = Carbon::parse($request->query('end_date'), 'Africa/Cairo')->endOfDay();

    // 2. جلب البيانات بناءً على التواريخ
    $readings = \App\Models\Glucose::where('record_glucose.user_id', $user->id)
        ->join('logs', 'record_glucose.log_id', '=', 'logs.log_id')
        ->whereBetween('logs.logged_at', [$startDate, $endDate])
        ->orderBy('logs.logged_at', 'desc')
        ->select('record_glucose.*', 'logs.logged_at as log_logged_at')
        ->get();

    // إذا لم توجد بيانات في هذه الفترة
    if ($readings->isEmpty()) {
        return response()->json(['message' => 'No data found for this period.'], 404);
    }

    // 3. حساب الإحصائيات (على البيانات المفلترة فقط)
    $count = $readings->count();
    $avgGlucose = round($readings->avg('glucose_level'));
    
    $lowest = $readings->sortBy('glucose_level')->first();
    $highest = $readings->sortByDesc('glucose_level')->first();

    // 4. تنسيق القراءات
    $formattedReadings = $readings->map(function($r) {
        $logTime = Carbon::parse($r->log_logged_at, 'UTC')->setTimezone('Africa/Cairo');
        return [
            'logId'        => $r->log_id,
            'glucoseValue' => (int) $r->glucose_level,
            'readingType'  => $r->reading_type,
            'loggedAt'     => $logTime->format('Y-m-d h:i A'),
        ];
    });

    return response()->json([
        'reportStatistics' => [
            'logsCount'      => $count,
            'averageGlucose' => $avgGlucose,
            'estimatedA1C'   => round(($avgGlucose + 46.7) / 28.7, 1),
            'lowestLog'      => [
                'logId'        => $lowest->log_id,
                'glucoseValue' => (int) $lowest->glucose_level,
                'readingType'  => $lowest->reading_type,
                'loggedAt'     => Carbon::parse($lowest->log_logged_at)->setTimezone('Africa/Cairo')->format('Y-m-d h:i A'),
            ],
            'highestLog'     => [
                'logId'        => $highest->log_id,
                'glucoseValue' => (int) $highest->glucose_level,
                'readingType'  => $highest->reading_type,
                'loggedAt'     => Carbon::parse($highest->log_logged_at)->setTimezone('Africa/Cairo')->format('Y-m-d h:i A'),
            ]
        ],
        'glucoseReadings' => [
            'all'       => $formattedReadings,
            'fasting'   => $formattedReadings->filter(fn($r) => $r['readingType'] == 'Fasting')->values(),
            'pre_meal'  => $formattedReadings->filter(fn($r) => in_array($r['readingType'], ['Before Meal', 'Pre Meal']))->values(),
            'post_meal' => $formattedReadings->filter(fn($r) => in_array($r['readingType'], ['After Meal', 'Post Meal']))->values(),
            'random'    => $formattedReadings->filter(fn($r) => $r['readingType'] == 'Random')->values(),
        ]
    ], 200);
}
}