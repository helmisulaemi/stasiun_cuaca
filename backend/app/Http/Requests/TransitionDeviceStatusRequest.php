<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TransitionDeviceStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => 'required|string|in:' . implode(',', config('device.statuses')),
            'reason' => 'nullable|string|max:500',
        ];
    }
}
