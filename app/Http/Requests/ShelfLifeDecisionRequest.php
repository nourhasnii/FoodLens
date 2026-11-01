<?php

namespace App\Http\Requests;

use App\Models\ShelfLifeAssessment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Décision d'un responsable sur la DLC réelle prédite d'un lot. */
class ShelfLifeDecisionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'decision' => ['required', Rule::in([ShelfLifeAssessment::ACCEPTED, ShelfLifeAssessment::REJECTED])],
            // Garder la DLC imprimée contre l'avis du modèle doit être justifié
            'note' => ['nullable', 'required_if:decision,'.ShelfLifeAssessment::REJECTED, 'string', 'max:1000'],
        ];
    }

    public function attributes(): array
    {
        return ['decision' => 'décision', 'note' => 'justification'];
    }

    public function messages(): array
    {
        return ['note.required_if' => 'Expliquez pourquoi vous gardez la DLC imprimée (ex. : contrôle qualité conforme).'];
    }
}
