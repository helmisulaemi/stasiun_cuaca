<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBatchTelemetryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'fw' => 'required|string|max:50',
            'batch' => 'required|array|min:1|max:' . config('ingest.max_batch', 500),
            'batch.*.ts' => 'required|integer|min:0',
            'batch.*.seq' => 'required|integer|min:0',
            'batch.*.battery_v' => 'required|numeric',
            'batch.*.rssi' => 'required|integer',
            'batch.*.readings' => 'required|array|min:1',
            'batch.*.readings.*.s' => 'required|string|max:50',
            'batch.*.readings.*.v' => 'required|numeric',
        ];
    }
}
