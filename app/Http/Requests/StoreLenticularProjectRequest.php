<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreLenticularProjectRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:50'],
            'print_size' => ['required', 'in:A3,A4,A5,15x10'],
            'print_service' => ['nullable', 'boolean'],
            'printer_dpi' => ['required_unless:print_service,1', 'nullable', 'integer', 'between:300,4800'],
            'lpi' => ['required', 'integer', 'in:50,60,75'],
        ];
    }
}
