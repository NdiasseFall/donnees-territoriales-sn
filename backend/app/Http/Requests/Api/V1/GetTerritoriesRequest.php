<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class GetTerritoriesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'level' => 'nullable|integer|between:0,6',
            'parent_code' => 'nullable|string|max:50',
            'limit' => 'nullable|integer|min:1|max:500',
            'offset' => 'nullable|integer|min:0',
            'simplified' => 'nullable|boolean',
            'tolerance' => 'nullable|numeric|min:0.00001|max:0.1',
            'format' => 'nullable|string|in:json,geojson',
        ];
    }
}
