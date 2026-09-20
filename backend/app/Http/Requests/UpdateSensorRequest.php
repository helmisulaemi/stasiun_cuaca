<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSensorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sensor_type_id' => 'sometimes|uuid|exists:sensor_types,id',
            'serial_number' => 'sometimes|string|max:100|unique:sensors,serial_number,' . $this->route('id'),
            'model' => 'sometimes|nullable|string|max:100',
        ];
    }
}
