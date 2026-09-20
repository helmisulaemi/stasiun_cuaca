<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSensorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sensor_type_id' => 'required|uuid|exists:sensor_types,id',
            'serial_number' => 'required|string|max:100|unique:sensors,serial_number',
            'model' => 'nullable|string|max:100',
        ];
    }
}
