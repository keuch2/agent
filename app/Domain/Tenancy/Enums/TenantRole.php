<?php

namespace App\Domain\Tenancy\Enums;

/**
 * Roles inside a tenant (spatie/laravel-permission with teams = tenant_id).
 * The platform superadmin is the `users.is_superadmin` flag, not a role.
 */
enum TenantRole: string
{
    case Admin = 'tenant_admin';
    case Supervisor = 'supervisor';
    case Advisor = 'advisor';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrador',
            self::Supervisor => 'Supervisor',
            self::Advisor => 'Asesor',
        };
    }

    /**
     * @return list<TenantPermission>
     */
    public function permissions(): array
    {
        return match ($this) {
            self::Admin => TenantPermission::cases(),
            self::Supervisor => [
                TenantPermission::ViewConversations,
                TenantPermission::ReplyConversations,
                TenantPermission::ViewAnalytics,
                TenantPermission::ManageKnowledge,
            ],
            self::Advisor => [
                TenantPermission::ViewConversations,
                TenantPermission::ReplyConversations,
            ],
        };
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(fn (self $r) => ['value' => $r->value, 'label' => $r->label()], self::cases());
    }
}
