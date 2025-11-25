<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TimeLogResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $netDuration = $this->duration_minutes - $this->paused_duration_minutes;
        $hours = floor($netDuration / 60);
        $minutes = $netDuration % 60;

        return [
            'id' => $this->id,
            'task_id' => $this->task_id,
            'user_id' => $this->user_id,
            'start_time' => $this->start_time?->toIso8601String(),
            'end_time' => $this->end_time?->toIso8601String(),
            'duration_minutes' => $netDuration,
            'duration_formatted' => sprintf('%02d:%02d', $hours, $minutes),
            'note' => $this->note,
            'status' => $this->status,
            'paused_at' => $this->paused_at?->toIso8601String(),
            'is_paused' => !is_null($this->paused_at),
            'user' => $this->whenLoaded('user', function () {
                return [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                    'email' => $this->user->email,
                ];
            }),
            'task' => $this->whenLoaded('task', function () {
                return [
                    'id' => $this->task->id,
                    'title' => $this->task->title,
                    'status' => $this->task->status,
                    'project' => $this->task->project ? [
                        'id' => $this->task->project->id,
                        'name' => $this->task->project->name,
                    ] : null,
                ];
            }),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
