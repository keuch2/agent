<?php

namespace App\Domain\Billing\Models;

use App\Domain\Tenancy\Models\Tenant;
use Database\Factories\PlanFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Global (not tenant-scoped). Limits and prices are always read from here.
 *
 * @property int $id
 * @property string $slug
 * @property string $name
 * @property string $monthly_price_usd
 * @property int $conversation_quota
 * @property int|null $advisor_limit null = unlimited
 * @property string $overage_price_usd
 * @property list<string> $features
 * @property bool $is_active
 */
#[Fillable(['slug', 'name', 'monthly_price_usd', 'conversation_quota', 'advisor_limit', 'overage_price_usd', 'features', 'is_active'])]
#[UseFactory(PlanFactory::class)]
class Plan extends Model
{
    /** @use HasFactory<PlanFactory> */
    use HasFactory;

    /**
     * Feature flags a plan can enable, with their panel labels.
     */
    public const array FEATURES = [
        'advanced_handoff_rules' => 'Reglas de derivación por horario, tema y prioridad',
        'integrations' => 'Integraciones con sistemas del cliente (catálogo, stock, CRM)',
    ];

    protected function casts(): array
    {
        return [
            'monthly_price_usd' => 'decimal:2',
            'overage_price_usd' => 'decimal:4',
            'features' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function hasFeature(string $feature): bool
    {
        return in_array($feature, $this->features, true);
    }

    /**
     * @return HasMany<Tenant, $this>
     */
    public function tenants(): HasMany
    {
        return $this->hasMany(Tenant::class);
    }
}
