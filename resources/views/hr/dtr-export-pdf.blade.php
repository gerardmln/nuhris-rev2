<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Daily Time Record</title>
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 11px; color: #1e293b; margin: 0; padding: 20px; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #00386f; padding-bottom: 15px; }
        .header h1 { margin: 0; font-size: 20px; color: #00386f; }
        .header p { margin: 3px 0; color: #64748b; font-size: 11px; }
        .info { margin-bottom: 15px; }
        .info td { padding: 3px 10px 3px 0; }
        .info .label { font-weight: bold; color: #475569; width: 120px; }
        .summary { display: flex; margin-bottom: 15px; }
        .summary-box { display: inline-block; width: 22%; padding: 8px; margin-right: 2%; border: 1px solid #e2e8f0; border-radius: 6px; text-align: center; }
        .summary-box .num { font-size: 18px; font-weight: bold; }
        .summary-box .lbl { font-size: 9px; color: #64748b; }
        table.dtr { width: 100%; border-collapse: collapse; margin-top: 10px; }
        table.dtr th { background: #00386f; color: white; padding: 6px 8px; text-align: left; font-size: 10px; }
        table.dtr td { padding: 5px 8px; border-bottom: 1px solid #e2e8f0; font-size: 10px; }
        table.dtr tr:nth-child(even) { background: #f8fafc; }
        .weekend { color: #94a3b8; font-style: italic; }
        .absent { color: #dc2626; }
        .present { color: #059669; }
        .footer { margin-top: 30px; text-align: center; font-size: 9px; color: #94a3b8; border-top: 1px solid #e2e8f0; padding-top: 10px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>NU HRIS - Daily Time Record</h1>
        <p>National University Human Resource Information System</p>
    </div>

    <table class="info">
        <tr><td class="label">Employee:</td><td>{{ $employee?->full_name ?? 'N/A' }}</td></tr>
        <tr><td class="label">Department:</td><td>{{ $employee?->department?->name ?? 'Unassigned' }}</td></tr>
        <tr><td class="label">Period:</td><td>{{ $period_label }}</td></tr>
        <tr><td class="label">Approved Schedule:</td><td>{{ $schedule_summary }}</td></tr>
    </table>

    <table style="width:100%; margin-bottom: 15px;">
        <tr>
            <td style="width:25%; text-align:center; border:1px solid #e2e8f0; border-radius:6px; padding:8px;">
                <div style="font-size:20px; font-weight:bold; color:#059669;">{{ $summary['present_days'] }}</div>
                <div style="font-size:9px; color:#64748b;">Present Days</div>
            </td>
            <td style="width:25%; text-align:center; border:1px solid #e2e8f0; border-radius:6px; padding:8px;">
                <div style="font-size:20px; font-weight:bold; color:#dc2626;">{{ $summary['absent_days'] }}</div>
                <div style="font-size:9px; color:#64748b;">Absent Days</div>
            </td>
            <td style="width:25%; text-align:center; border:1px solid #e2e8f0; border-radius:6px; padding:8px;">
                <div style="font-size:20px; font-weight:bold; color:#d97706;">{{ $summary['tardiness_total'] }} min</div>
                <div style="font-size:9px; color:#64748b;">Total Tardiness</div>
            </td>
            <td style="width:25%; text-align:center; border:1px solid #e2e8f0; border-radius:6px; padding:8px;">
                <div style="font-size:20px; font-weight:bold; color:#7c3aed;">{{ $summary['undertime_total'] }} min</div>
                <div style="font-size:9px; color:#64748b;">Total Undertime</div>
            </td>
        </tr>
    </table>

    <table class="dtr">
        <thead>
            <tr>
                <th>Date</th>
                <th>Day</th>
                <th>Time In</th>
                <th>Time Out</th>
                <th>Tardiness</th>
                <th>Undertime</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($records as $record)
                <tr>
                    <td>{{ $record['date'] }}</td>
                    <td>{{ $record['day'] }}</td>
                    <td>{{ $record['time_in'] }}</td>
                    <td>{{ $record['time_out'] }}</td>
                    <td>{{ $record['tardiness_minutes'] ? $record['tardiness_minutes'].' min' : '-' }}</td>
                    <td>{{ $record['undertime_minutes'] ? $record['undertime_minutes'].' min' : '-' }}</td>
                    <td class="{{ $record['status'] === 'Weekend' ? 'weekend' : ($record['status'] === 'Not Present' ? 'absent' : 'present') }}">
                        {{ $record['status'] }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        Generated on {{ now()->format('F j, Y h:i A') }} | NU HRIS
    </div>
</body>
</html>
