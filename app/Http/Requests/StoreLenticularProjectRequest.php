<?php

namespace App\Http\Requests;

use App\Services\LenticularAccessService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
    public function rules(LenticularAccessService $access): array
    {
        $plan = $access->plan($this->user());

        return [
            'name' => ['required', 'string', 'max:50'],
            'print_size' => ['required', 'in:A3,A4,A5,15x10'],
            'printer_dpi' => ['required', 'integer', Rule::in($access->printerDpis($plan))],
            'lpi' => ['required', 'integer', 'in:50,60,75'],
        ];
    }
}
