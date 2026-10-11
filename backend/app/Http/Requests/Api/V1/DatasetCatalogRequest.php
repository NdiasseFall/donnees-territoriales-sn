<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use App\Domain\Dataset\Enums\AccessLevel;
use Illuminate\Foundation\Http\FormRequest;

class DatasetCatalogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Normalisation AVANT validation (CDC) : l'enum AccessLevel est en
     * MAJUSCULES, la direction en minuscules — le filtre reste insensible
     * à la casse côté client (?access_level=public == PUBLIC).
     */
    protected function prepareForValidation(): void
    {
        $merge = [];

        if ($this->has('access_level') && is_string($this->input('access_level'))) {
            $merge['access_level'] = strtoupper(trim((string) $this->input('access_level')));
        }

        if ($this->has('direction') && is_string($this->input('direction'))) {
            $merge['direction'] = strtolower(trim((string) $this->input('direction')));
        }

        foreach (['q', 'search', 'category', 'order'] as $key) {
            if ($this->has($key) && is_string($this->input($key))) {
                $merge[$key] = trim((string) $this->input($key));
            }
        }

        if ($merge !== []) {
            $this->merge($merge);
        }
    }

    public function rules(): array
    {
        return [
            'q' => 'nullable|string|min:2|max:100',
            'search' => 'nullable|string|min:2|max:100',
            'category' => 'nullable|string|max:50',
            'access_level' => 'nullable|string|in:'.implode(',', array_column(AccessLevel::cases(), 'value')),
            'order' => 'nullable|string|in:name,updated_at,created_at',
            'direction' => 'nullable|string|in:asc,desc',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:500',
            'limit' => 'nullable|integer|min:1|max:500',
            'offset' => 'nullable|integer|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'q.min' => 'Le terme de recherche doit contenir au moins 2 caractères (§85).',
            'search.min' => 'Le terme de recherche doit contenir au moins 2 caractères (§85).',
            'access_level.in' => 'Le niveau d\'accès doit être un des niveaux PUBLIC, REGISTERED, RESTRICTED, INTERNAL ou CONFIDENTIAL.',
            'order.in' => 'L\'ordre doit être name, updated_at ou created_at.',
            'direction.in' => 'Le sens doit être asc ou desc.',
            'per_page.max' => 'Le nombre d\'éléments par page ne peut pas dépasser 500.',
        ];
    }
}
