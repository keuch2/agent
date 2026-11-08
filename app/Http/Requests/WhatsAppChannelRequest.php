<?php

namespace App\Http\Requests;

use App\Domain\Channels\Enums\ChannelStatus;
use App\Domain\Tenancy\Models\Tenant;
use App\Domain\Tenancy\TenantContext;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Access is enforced on the routes: `can:channels.manage` for a tenant,
 * `superadmin` for the platform panel.
 */
class WhatsAppChannelRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $routeTenant = $this->route('tenant');
        $tenantId = $routeTenant instanceof Tenant ? $routeTenant->id : app(TenantContext::class)->idOrFail();

        return [
            // Checked across every tenant, which Row Level Security would hide.
            'phone_number_id' => [
                'required', 'string', 'regex:/^\d{5,32}$/',
                Rule::unique(TenantContext::ADMIN_CONNECTION.'.channels', 'phone_number_id')
                    ->where(fn (Builder $query) => $query
                        ->where('tenant_id', '!=', $tenantId)
                        ->where('status', '!=', ChannelStatus::Disconnected->value)),
            ],
            'waba_id' => ['required', 'string', 'regex:/^\d{5,32}$/'],
            'access_token' => ['nullable', 'string', 'max:4096'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'phone_number_id' => 'identificador del número',
            'waba_id' => 'identificador de la cuenta de WhatsApp',
            'access_token' => 'token de acceso',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ['phone_number_id.unique' => 'Este número ya está vinculado a otra empresa.'];
    }
}
