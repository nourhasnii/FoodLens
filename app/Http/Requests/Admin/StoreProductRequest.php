<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:3', 'max:100'],
            'description' => ['required', 'string', 'min:10', 'max:1000'],
            'category_id' => ['required', 'exists:categories,id'],
            'unit' => ['required', 'string', 'min:1', 'max:20'],
            'origin_country' => ['required', 'string', 'min:2', 'max:60'],
            'origin_region' => ['nullable', 'string', 'min:2', 'max:80'],
            'producer_id' => ['required', 'exists:users,id'],
            'barcode' => ['nullable', 'string', 'min:8', 'max:32', 'regex:/^[0-9A-Za-z\-]+$/', 'unique:products,barcode'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'carbon_footprint' => ['required', 'numeric', 'min:0', 'max:9999'],
            'verification_status' => ['required', 'in:pending,verified,rejected'],
            'is_organic' => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['is_organic' => $this->boolean('is_organic')]);
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Le nom du produit est obligatoire.',
            'name.string' => 'Le nom du produit doit être une chaîne de caractères.',
            'name.min' => 'Le nom doit contenir au moins 3 caractères.',
            'name.max' => 'Le nom ne peut pas dépasser 100 caractères.',
            'description.required' => 'La description est obligatoire.',
            'description.string' => 'La description doit être une chaîne de caractères.',
            'description.min' => 'La description doit contenir au moins 10 caractères.',
            'description.max' => 'La description ne peut pas dépasser 1000 caractères.',
            'category_id.required' => 'La catégorie est obligatoire.',
            'category_id.exists' => 'Veuillez sélectionner une catégorie valide.',
            'unit.required' => 'L’unité est obligatoire (max 20 caractères).',
            'unit.string' => 'L’unité doit être une chaîne de caractères.',
            'unit.min' => 'L’unité est obligatoire (max 20 caractères).',
            'unit.max' => 'L’unité est obligatoire (max 20 caractères).',
            'origin_country.string' => 'Le pays d’origine doit être une chaîne de caractères.',
            'origin_country.required' => 'Le pays d’origine est obligatoire.',
            'origin_country.min' => 'Le pays d’origine doit contenir au moins 2 caractères.',
            'origin_country.max' => 'Le pays d’origine ne peut pas dépasser 60 caractères.',
            'origin_region.string' => 'La région d’origine doit être une chaîne de caractères.',
            'origin_region.min' => 'La région d’origine doit contenir au moins 2 caractères.',
            'origin_region.max' => 'La région d’origine ne peut pas dépasser 80 caractères.',
            'producer_id.required' => 'Le producteur est obligatoire.',
            'producer_id.exists' => 'Veuillez sélectionner un producteur valide.',
            'barcode.string' => 'Le code-barres doit être une chaîne de caractères.',
            'barcode.min' => 'Le code-barres doit contenir au moins 8 caractères.',
            'barcode.max' => 'Le code-barres ne peut pas dépasser 32 caractères.',
            'barcode.regex' => 'Le code-barres contient des caractères invalides.',
            'barcode.unique' => 'Ce code-barres est déjà utilisé.',
            'image.image' => 'Le fichier doit être une image.',
            'image.mimes' => 'Formats acceptés : JPG, PNG, WEBP.',
            'image.max' => 'L’image ne doit pas dépasser 5 Mo.',
            'carbon_footprint.required' => 'L’empreinte carbone est obligatoire.',
            'carbon_footprint.numeric' => 'L’empreinte carbone doit être un nombre positif.',
            'carbon_footprint.min' => 'L’empreinte carbone doit être un nombre positif.',
            'carbon_footprint.max' => 'L’empreinte carbone ne peut pas dépasser 9999.',
            'verification_status.required' => 'Le statut de vérification est obligatoire.',
            'verification_status.in' => 'Le statut de vérification sélectionné est invalide.',
            'is_organic.boolean' => 'La valeur du champ biologique est invalide.',
        ];
    }
}