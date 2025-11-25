<?php

namespace App\Exports;

use App\Models\TimeLog;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Carbon\Carbon;

class TimeLogExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    protected $timeLogs;

    public function __construct($timeLogs)
    {
        $this->timeLogs = $timeLogs;
    }

    public function collection()
    {
        return $this->timeLogs;
    }

    public function headings(): array
    {
        return [
            'ID',
            'User',
            'Task',
            'Project',
            'Date',
            'Start Time',
            'End Time',
            'Duration (Hours)',
            'Duration (Formatted)',
            'Note',
            'Status',
        ];
    }

    public function map($timeLog): array
    {
        $netMinutes = $timeLog->duration_minutes - $timeLog->paused_duration_minutes;
        $hours = $netMinutes / 60;
        $formatted = $this->formatDurationMinutes($netMinutes);

        return [
            $timeLog->id,
            $timeLog->user->name ?? 'N/A',
            $timeLog->task->title ?? 'N/A',
            $timeLog->task->project->name ?? 'N/A',
            Carbon::parse($timeLog->start_time)->format('Y-m-d'),
            Carbon::parse($timeLog->start_time)->format('H:i:s'),
            $timeLog->end_time ? Carbon::parse($timeLog->end_time)->format('H:i:s') : 'N/A',
            number_format($hours, 2),
            $formatted,
            $timeLog->note ?? '',
            ucfirst($timeLog->status),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }

    private function formatDurationMinutes($minutes)
    {
        $hours = floor($minutes / 60);
        $mins = $minutes % 60;
        return sprintf('%02d:%02d', $hours, $mins);
    }
}

