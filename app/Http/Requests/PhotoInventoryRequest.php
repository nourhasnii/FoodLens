<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Envoi d'une photo de rayon / palette pour l'inventaire par IA. */
class PhotoInventoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'site_id' => ['required', 'integer', Rule::exists('sites', 'id')->where('is_active', true)],
            'photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:'.(int) config('stock.photo_inventory.max_photo_kb', 8192)],
        ];
    }

    public function attributes(): array
    {
        return ['site_id' => 'site', 'photo' => 'photo'];
    }
}
