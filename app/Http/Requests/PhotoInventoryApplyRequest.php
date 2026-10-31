<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** Comptage validé par l'utilisateur (une quantité par produit, vide = non compté). */
class PhotoInventoryApplyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'counts' => ['required', 'array'],
            'counts.*' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
        ];
    }

    public function attributes(): array
    {
        return ['counts.*' => 'quantité comptée'];
    }

    public function messages(): array
    {
        return ['counts.required' => 'Indiquez au moins une quantité comptée.'];
    }
}
