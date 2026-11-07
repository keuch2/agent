<?php

namespace App\Http\Requests\Tenant;

use App\Domain\Tenancy\Enums\TenantRole;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Create and update a user of the current tenant.
 */
class TenantUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        $target = $this->route('user');

        return $target instanceof User
            ? $this->user()->can('update', $target)
            : $this->user()->can('create', User::class);
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['email' => mb_strtolower(trim((string) $this->input('email')))]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $target = $this->route('user');
        $editingSelf = $target instanceof User && $target->is($this->user());

        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:254', Rule::unique('users', 'email')->ignore($target)->withoutTrashed()],
            // An administrator cannot demote themselves and lock the tenant out.
            'role' => array_filter(['required', Rule::enum(TenantRole::class), $editingSelf ? Rule::in([TenantRole::Admin->value]) : null]),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['name' => 'nombre', 'email' => 'correo', 'role' => 'rol'];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ['role.in' => 'No podés quitarte el rol de administrador.'];
    }
}
