<?php

namespace App\Modules\Inventory\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreStockAdjustmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $type = $this->input('adjustment_type');
            $quantity = (int) $this->input('quantity', 0);

            if (in_array($type, ['increase', 'decrease'], true) && $quantity < 1) {
                $validator->errors()->add('quantity', 'Quantity must be at least 1 for increase or decrease adjustment.');
            }
        });
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'adjustment_type' => ['required', 'in:increase,decrease,set'],
            'quantity' => ['required', 'integer', 'min:0'],
        ];
    }
}
