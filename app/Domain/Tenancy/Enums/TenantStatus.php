<?php

namespace App\Domain\Tenancy\Enums;

enum TenantStatus: string
{
    case Onboarding = 'onboarding';
    case Active = 'active';
    case Suspended = 'suspended';

    public function label(): string
    {
        return match ($this) {
            self::Onboarding => 'En activación',
            self::Active => 'Activa',
            self::Suspended => 'Suspendida',
        };
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(fn (self $s) => ['value' => $s->value, 'label' => $s->label()], self::cases());
    }
}
