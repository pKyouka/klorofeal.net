<?php

namespace App\Modules\Products\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImportProductsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'File CSV harus dipilih.',
            'file.mimes'    => 'File harus berformat CSV (.csv).',
            'file.max'      => 'Ukuran file maksimal 2MB.',
        ];
    }
}
