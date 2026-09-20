<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTelemetryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'fw' => 'required|string|max:50',
            'ts' => 'required|integer|min:0',
            'seq' => 'required|integer|min:0',
            'battery_v' => 'required|numeric',
            'rssi' => 'required|integer',
            'readings' => 'required|array|min:1',
            'readings.*.s' => 'required|string|max:50',
            'readings.*.v' => 'required|numeric',
        ];
    }
}
