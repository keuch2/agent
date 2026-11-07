<?php

namespace App\Http\Requests\Admin;

use App\Domain\Billing\Models\Plan;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('plan'));
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:60'],
            'monthly_price_usd' => ['required', 'numeric', 'min:0', 'max:99999999'],
            'conversation_quota' => ['required', 'integer', 'min:0'],
            'advisor_limit' => ['nullable', 'integer', 'min:1'],
            'overage_price_usd' => ['required', 'numeric', 'min:0', 'max:999999'],
            'features' => ['array'],
            'features.*' => ['string', Rule::in(array_keys(Plan::FEATURES))],
            'is_active' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'nombre',
            'monthly_price_usd' => 'precio mensual',
            'conversation_quota' => 'cupo de conversaciones',
            'advisor_limit' => 'límite de asesores',
            'overage_price_usd' => 'precio del excedente',
            'features' => 'funciones',
        ];
    }
}
