<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class ReverseGeocodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'lon' => 'required|numeric|between:-180,180',
            'lat' => 'required|numeric|between:-90,90',
            'level' => 'nullable|integer|between:0,6',
        ];
    }
}
