<?php

namespace App\Http\Requests\Stock;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStockMovementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (!$this->filled('idempotency_key') && $this->headers->has('Idempotency-Key')) {
            $this->merge(['idempotency_key' => $this->header('Idempotency-Key')]);
        }
    }

    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(['receipt', 'issue', 'transfer'])],
            'product_id' => ['required', 'uuid', 'exists:products,id'],
            'variant_id' => ['nullable', 'uuid', 'exists:product_variants,id'],
            'quantity' => ['required', 'numeric', 'gt:0'],
            'source_warehouse_id' => ['nullable', 'uuid', 'exists:warehouses,id'],
            'destination_warehouse_id' => ['nullable', 'uuid', 'exists:warehouses,id'],
            'source_location_id' => ['nullable', 'uuid', 'exists:locations,id'],
            'destination_location_id' => ['nullable', 'uuid', 'exists:locations,id'],
            'reason' => ['nullable', 'string', 'max:2000'],
            'reference_type' => ['nullable', 'string', 'max:50'],
            'reference_id' => ['nullable', 'uuid'],
            'idempotency_key' => ['nullable', 'string', 'max:100'],
        ];
    }
}
