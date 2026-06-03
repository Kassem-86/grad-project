<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>seen - Blood Glucose Report</title>
    <style>
        :root {
            --primary-blue: #6976EB;
            --light-blue: #eef2fb;
            --border-blue: #bdcbf0;
            /* ألوان قوية للنصوص */
            --text-high: #FB2C36;
            --text-normal: #17CE92;
            --text-low: #FF9800;
            /* ألوان فاتحة للخلفيات */
            --bg-high: #ffeef0;
            --bg-normal: #e8faf5;
            --bg-low: #fff8e1;
        }

        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; color: #333; background-color: #ffffff; padding: 20px; }
        .header { text-align: center; margin-bottom: 25px; border-bottom: 2px solid var(--primary-blue); padding-bottom: 15px; }
        .user-info { margin-bottom: 25px; font-size: 13px; background: #ffffff; padding: 12px; border-left: 4px solid var(--primary-blue); border: 1px solid var(--border-blue); border-radius: 5px; }
        .stats-box { background: var(--light-blue); border: 1px solid var(--border-blue); text-align: center; padding: 12px; border-radius: 6px; }
        .stats-value { font-size: 20px; font-weight: bold; color: var(--primary-blue); margin-top: 4px; }
        .stats-label { font-size: 10px; color: #555; text-transform: uppercase; font-weight: bold; }
        
        .section-title { color: var(--primary-blue); border-bottom: 1px solid var(--primary-blue); padding-bottom: 5px; margin-top: 25px; font-size: 16px; }
        
        table.readings-table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        table.readings-table th { background-color: var(--primary-blue); color: white; padding: 10px; font-size: 12px; text-transform: uppercase; }
        table.readings-table td { padding: 10px; border-bottom: 1px solid var(--border-blue); text-align: center; font-size: 12px; }
        
        /* الصناديق الملونة (Style المطلوب) */
        .glucose-box {
            padding: 5px 12px;
            border-radius: 6px;
            font-weight: bold;
            display: inline-block;
            min-width: 70px;
        }
        .box-high { background-color: var(--bg-high); color: var(--text-high); }
        .box-normal { background-color: var(--bg-normal); color: var(--text-normal); }
        .box-low { background-color: var(--bg-low); color: var(--text-low); }
        
        .type-badge { font-weight: normal; color: #666; font-size: 11px; font-style: italic; }
    </style>
</head>
<body>

    <div class="header">
        <img src="{{ $base64 }}" alt="Logo" style="width: 140px; height: auto;">
    </div>

    <div class="user-info">
        <strong>Patient Name:</strong> {{ $username }} <br>
        <strong>Report Period:</strong> From {{ $start_date }} To {{ $end_date }} ({{ $days }} Days) <br>
        <strong>Generated On:</strong> {{ now()->format('Y-m-d H:i A') }}
    </div>

    <table style="width: 100%; margin-bottom: 30px;">
        <tr>
            <td style="width: 25%; padding-right: 5px;"><div class="stats-box"><div class="stats-label">Minimum</div><div class="stats-value">{{ $stats['lowest_glucose'] }}</div></div></td>
            <td style="width: 25%; padding-left: 5px; padding-right: 5px;"><div class="stats-box"><div class="stats-label">Average</div><div class="stats-value">{{ $stats['avg_glucose'] }}</div></div></td>
            <td style="width: 25%; padding-left: 5px; padding-right: 5px;"><div class="stats-box"><div class="stats-label">Maximum</div><div class="stats-value">{{ $stats['highest_glucose'] }}</div></div></td>
            <td style="width: 25%; padding-left: 5px;"><div class="stats-box"><div class="stats-label">HbA1c</div><div class="stats-value">{{ $stats['a1c_estimation'] }}%</div></div></td>
        </tr>
    </table>

    <h3 class="section-title">Detailed Logs History</h3>
    
    <table class="readings-table">
        <thead>
            <tr>
                <th>Date</th>
                <th>Time</th>
                <th>Glucose Level</th>
                <th>Type</th>
            </tr>
        </thead>
        <tbody>
            @foreach($readings as $reading)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($reading->log_created_at)->format('Y-m-d') }}</td>
                    <td>{{ \Carbon\Carbon::parse($reading->log_created_at)->format('h:i A') }}</td>
                    <td>
                        @if($reading->glucose_level < 70)
                            <span class="glucose-box box-low">{{ $reading->glucose_level }} mg/dL</span>
                        @elseif($reading->glucose_level > 220)
                            <span class="glucose-box box-high">{{ $reading->glucose_level }} mg/dL</span>
                        @else
                            <span class="glucose-box box-normal">{{ $reading->glucose_level }} mg/dL</span>
                        @endif
                    </td>
                    <td class="type-badge">{{ str_replace('_', ' ', ucfirst($reading->reading_type)) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

</body>
</html>