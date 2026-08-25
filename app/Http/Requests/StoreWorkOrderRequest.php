<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreWorkOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('workshop.manage');
    }

    public function rules(): array
    {
        return [
            'customer_vehicle_id' => ['required', 'integer', 'exists:customer_vehicles,id'],
            'mechanic_id' => ['nullable', 'integer', 'exists:mechanics,id'],
            'type' => ['required', 'in:servicio,reparacion'],
            'service_scope' => ['nullable', 'in:menor,mayor'],
            'mileage_in' => ['nullable', 'numeric', 'min:0'],
            'reported_issue' => ['nullable', 'string', 'max:2000'],
            'estimated_delivery_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'failure_ids' => ['nullable', 'array'],
            'failure_ids.*' => ['integer', 'exists:common_failures,id'],
            'service_ids' => ['nullable', 'array'],
            'service_ids.*' => ['integer', 'exists:common_services,id'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $isService = $this->input('type') === 'servicio';

            if (! $isService && ! $this->filled('reported_issue') && empty($this->input('failure_ids', []))) {
                $validator->errors()->add(
                    'reported_issue',
                    'Selecciona al menos un problema o describe el problema reportado.',
                );
            }
        });
    }
}
