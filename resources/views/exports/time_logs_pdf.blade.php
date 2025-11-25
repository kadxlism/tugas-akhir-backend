<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Time Logs Export</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .summary {
            margin-top: 20px;
            padding: 10px;
            background-color: #f9f9f9;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Time Logs Report</h1>
        <p>Generated on: {{ now()->format('Y-m-d H:i:s') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>User</th>
                <th>Task</th>
                <th>Project</th>
                <th>Date</th>
                <th>Start Time</th>
                <th>End Time</th>
                <th>Duration</th>
                <th>Status</th>
                <th>Type</th>
            </tr>
        </thead>
        <tbody>
            @foreach($timeLogs as $log)
            <tr>
                <td>{{ $log->id }}</td>
                <td>{{ $log->user->name ?? 'N/A' }}</td>
                <td>{{ $log->task->title ?? 'N/A' }}</td>
                <td>{{ $log->project->name ?? 'N/A' }}</td>
                <td>{{ \Carbon\Carbon::parse($log->start_time)->format('Y-m-d') }}</td>
                <td>{{ \Carbon\Carbon::parse($log->start_time)->format('H:i:s') }}</td>
                <td>{{ $log->end_time ? \Carbon\Carbon::parse($log->end_time)->format('H:i:s') : 'N/A' }}</td>
                <td>
                    @php
                        $duration = $log->duration - $log->paused_duration;
                        $hours = floor($duration / 3600);
                        $minutes = floor(($duration % 3600) / 60);
                        $seconds = $duration % 60;
                    @endphp
                    {{ sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds) }}
                </td>
                <td>{{ ucfirst($log->status) }}</td>
                <td>{{ $log->is_manual ? 'Manual' : 'Timer' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="summary">
        <h3>Summary</h3>
        <p><strong>Total Logs:</strong> {{ $timeLogs->count() }}</p>
        <p><strong>Total Duration:</strong>
            @php
                $totalSeconds = $timeLogs->sum(function($log) {
                    return $log->duration - $log->paused_duration;
                });
                $totalHours = floor($totalSeconds / 3600);
                $totalMinutes = floor(($totalSeconds % 3600) / 60);
            @endphp
            {{ $totalHours }} hours {{ $totalMinutes }} minutes
        </p>
    </div>
</body>
</html>

