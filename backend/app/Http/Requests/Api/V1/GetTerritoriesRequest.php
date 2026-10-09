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
            'type' => 'nullable|string|max:50',
            'parent' => 'nullable|string|max:50',
            'parent_code' => 'nullable|string|max:50',
            'status' => 'nullable|string|in:ACTIVE,INACTIVE,PENDING,DISPUTED,HISTORICAL',
            'search' => 'nullable|string|max:100',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:500',
            'limit' => 'nullable|integer|min:1|max:500',
            'offset' => 'nullable|integer|min:0',
            'simplified' => 'nullable|boolean',
            'tolerance' => 'nullable|numeric|min:0.00001|max:0.1',
            'format' => 'nullable|string|in:json,geojson',
            // US-012 : BBOX min_lon,min_lat,max_lon,max_lat en requête préparée.
            'bbox' => ['nullable', 'string', 'regex:/^-?\d+(\.\d+)?,-?\d+(\.\d+)?,-?\d+(\.\d+)?,-?\d+(\.\d+)?$/'],
        ];
    }
}
