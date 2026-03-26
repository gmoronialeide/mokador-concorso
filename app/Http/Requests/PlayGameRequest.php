<?php

namespace App\Http\Requests;

use App\Models\Store;
use Illuminate\Foundation\Http\FormRequest;

class PlayGameRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'store_id' => [
                'required',
                'integer',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! Store::where('id', $value)->where('is_active', true)->exists()) {
                        $fail('Il punto vendita non è valido o non è attivo.');
                    }
                },
            ],
            'receipt' => ['required', 'file', 'mimes:jpg,jpeg,png', 'max:6144'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'store_id.required' => 'Seleziona il punto vendita.',
            'receipt.required' => 'Carica la foto dello scontrino.',
            'receipt.mimes' => 'Lo scontrino deve essere un\'immagine JPG o PNG.',
            'receipt.max' => 'Lo scontrino non può superare i 6MB.',
        ];
    }
}
