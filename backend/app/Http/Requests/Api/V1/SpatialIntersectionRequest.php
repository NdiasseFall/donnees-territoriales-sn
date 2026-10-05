<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class SpatialIntersectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'geometry' => 'required|string',
            'level' => 'nullable|integer|between:0,6',
            'limit' => 'nullable|integer|min:1|max:200',
            'format' => 'nullable|string|in:json,geojson',
        ];
    }
}
