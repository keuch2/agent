<?php

namespace App\Domain\Tenancy\Models;

use App\Domain\Billing\Models\Plan;
use App\Domain\Tenancy\Enums\TenantStatus;
use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Factories\TenantFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Row Level Security on `tenants` only exposes the active tenant's own row to
 * the runtime connection; the superadmin panel reads it through the platform one.
 *
 * @property int $id
 * @property string $name
 * @property string|null $legal_name
 * @property string|null $ruc
 * @property string $timezone
 * @property TenantStatus $status
 * @property int $plan_id
 * @property array<string, mixed> $settings
 * @property CarbonImmutable|null $activated_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Plan $plan
 */
#[Fillable(['name', 'legal_name', 'ruc', 'timezone', 'status', 'plan_id', 'settings'])]
#[UseFactory(TenantFactory::class)]
class Tenant extends Model
{
    /** @use HasFactory<TenantFactory> */
    use HasFactory, SoftDeletes;

    protected $attributes = [
        'timezone' => 'America/Asuncion',
        'status' => 'onboarding',
        'settings' => '{}',
    ];

    protected function casts(): array
    {
        return [
            'status' => TenantStatus::class,
            'settings' => 'array',
            'activated_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Plan, $this>
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    /**
     * @return HasMany<User, $this>
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function isSuspended(): bool
    {
        return $this->status === TenantStatus::Suspended;
    }
}
