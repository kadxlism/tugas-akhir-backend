<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StartTimerRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // All authenticated users can start timers
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
            'note' => 'nullable|string|max:1000',
        ];
    }
}
