<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('products.manage');
    }

    public function rules(): array
    {
        $product = $this->route('product');

        return [
            'category_id' => ['required', 'exists:categories,id'],
            'sku' => ['required', 'string', 'max:50', Rule::unique('products', 'sku')->ignore($product)],
            'barcode' => ['nullable', 'string', 'max:50', Rule::unique('products', 'barcode')->ignore($product)],
            'name' => ['required', 'string', 'max:255'],
            'brand' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'images' => ['nullable', 'array'],
            'images.*' => ['image', 'max:2048'],
            'remove_image_ids' => ['nullable', 'array'],
            'remove_image_ids.*' => ['integer', 'exists:product_images,id'],
            'unit' => ['required', 'in:unidad,litro,galon,cuarto,par,juego'],
            'is_bulk' => ['boolean'],
            'cost_price' => ['required', 'numeric', 'min:0'],
            'sale_price' => ['required', 'numeric', 'min:0'],
            'compare_at_price' => ['nullable', 'numeric', 'gt:sale_price'],
            'min_stock' => ['required', 'numeric', 'min:0'],
            'expiration_date' => ['nullable', 'date'],
            'active' => ['boolean'],
            'compatibilities' => ['nullable', 'array'],
            'compatibilities.*.brand' => ['required_with:compatibilities', 'string', 'max:100'],
            'compatibilities.*.model' => ['required_with:compatibilities', 'string', 'max:100'],
            'compatibilities.*.year_from' => ['nullable', 'integer', 'min:1950', 'max:2100'],
            'compatibilities.*.year_to' => ['nullable', 'integer', 'min:1950', 'max:2100'],
            'compatibilities.*.engine' => ['nullable', 'string', 'max:100'],
            'specifications' => ['nullable', 'array'],
            'specifications.*.label' => ['required_with:specifications', 'string', 'max:100'],
            'specifications.*.value' => ['required_with:specifications', 'string', 'max:255'],
        ];
    }
}
