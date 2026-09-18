<?php

namespace App\Http\Requests\Catalog;

use App\Services\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = $this->user()?->tenant_id ?? TenantContext::getTenantId();

        return [
            'sku' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('products', 'sku')->where('tenant_id', $tenantId),
            ],
            'name' => ['required', 'string', 'max:255'],
            'base_unit_id' => ['required', 'uuid', 'exists:units,id'],
            'category_id' => ['nullable', 'uuid', 'exists:categories,id'],
            'category_name' => ['nullable', 'string', 'max:150'],
            'manufacturer' => ['nullable', 'string', 'max:100'],
            'reference' => ['nullable', 'string', 'max:100'],
            'quantity' => ['nullable', 'numeric', 'min:0'],
            'description' => ['nullable', 'string'],
            'state' => ['nullable', 'string', 'in:new,used,refurbished,damaged'],
            'alert_threshold' => ['nullable', 'numeric', 'min:0'],
            'has_variants' => ['boolean'],
            'requires_serial_number' => ['boolean'],
            'variants' => ['nullable', 'array'],
            'variants.*.sku' => ['required_with:variants', 'string', 'max:100'],
            'variants.*.barcode' => ['nullable', 'string', 'max:100'],
            'variants.*.qr_code' => ['nullable', 'string', 'max:100'],
            'variants.*.name' => ['nullable', 'string', 'max:255'],
            'variants.*.attribute_values' => ['nullable', 'array'],
        ];
    }
}
