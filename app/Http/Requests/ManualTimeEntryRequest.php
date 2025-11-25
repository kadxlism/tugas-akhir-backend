<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ManualTimeEntryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // All authenticated users can create manual entries
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'task_id' => 'required|uuid|exists:tasks,id',
            'date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'duration_minutes' => 'nullable|integer|min:0',
            'note' => 'nullable|string|max:1000',
        ];
    }
}
