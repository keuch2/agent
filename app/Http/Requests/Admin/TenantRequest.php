<?php

namespace App\Http\Requests\Admin;

use App\Domain\Tenancy\Enums\TenantStatus;
use App\Domain\Tenancy\Models\Tenant;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Create and update a tenant. On create, the first administrator is required.
 */
class TenantRequest extends FormRequest
{
    public function authorize(): bool
    {
        $tenant = $this->route('tenant');

        return $tenant instanceof Tenant
            ? $this->user()->can('update', $tenant)
            : $this->user()->can('create', Tenant::class);
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('admin_email')) {
            $this->merge(['admin_email' => mb_strtolower(trim((string) $this->input('admin_email')))]);
        }
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $tenant = $this->route('tenant');
        $creating = ! $tenant instanceof Tenant;

        return [
            'name' => ['required', 'string', 'max:120'],
            'legal_name' => ['nullable', 'string', 'max:200'],
            'ruc' => ['nullable', 'string', 'max:20', 'regex:/^\d{1,10}-\d$/', Rule::unique('tenants', 'ruc')->ignore($tenant)],
            'timezone' => ['required', 'timezone:all'],
            'plan_id' => ['required', 'integer', Rule::exists('plans', 'id')->where('is_active', true)],
            'status' => [$creating ? 'prohibited' : 'required', Rule::enum(TenantStatus::class)],
            'admin_name' => [$creating ? 'required' : 'prohibited', 'string', 'max:120'],
            'admin_email' => [$creating ? 'required' : 'prohibited', 'email', 'max:254', Rule::unique('users', 'email')->withoutTrashed()],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'nombre',
            'legal_name' => 'razón social',
            'ruc' => 'RUC',
            'timezone' => 'zona horaria',
            'plan_id' => 'plan',
            'status' => 'estado',
            'admin_name' => 'nombre del administrador',
            'admin_email' => 'correo del administrador',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ['ruc.regex' => 'El RUC debe tener el formato 80012345-6.'];
    }
}
