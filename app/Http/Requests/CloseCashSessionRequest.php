<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CloseCashSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        if (! $this->user()->can('cash-sessions.manage')) {
            return false;
        }

        $cashSession = $this->route('cashSession');

        return $cashSession && ((int) $cashSession->user_id === $this->user()->id || $this->user()->hasRole('admin'));
    }

    public function rules(): array
    {
        return [
            'closing_amount' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
