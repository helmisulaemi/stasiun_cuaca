<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSensorTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:50|unique:sensor_types,name',
            'unit' => 'required|string|max:20',
            'min_value' => 'required|numeric',
            'max_value' => 'required|numeric|gte:min_value',
            'precision' => 'required|integer|min:0|max:5',
        ];
    }
}
