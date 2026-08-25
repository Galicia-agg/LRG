<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCommonFailureRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('workshop.manage');
    }

    public function rules(): array
    {
        return [
            'description' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:100'],
            'suggested_price' => ['nullable', 'numeric', 'min:0'],
            'active' => ['boolean'],
        ];
    }
}
