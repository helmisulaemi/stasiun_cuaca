<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCalibrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'offset' => 'required|numeric',
            'scale' => 'required|numeric|min:0',
            'effective_from' => 'required|date|before_or_equal:now',
        ];
    }
}
