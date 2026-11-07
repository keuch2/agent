<?php

namespace App\Domain\Tenancy\Concerns;

use App\Domain\Tenancy\Exceptions\MissingTenantContext;
use App\Domain\Tenancy\Models\Tenant;
use App\Domain\Tenancy\Scopes\TenantScope;
use App\Domain\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/**
 * Every business model with a `tenant_id` column must use this trait.
 *
 * @mixin Model
 */
trait BelongsToTenant
{
    public static function bootBelongsToTenant(): void
    {
        static::addGlobalScope(new TenantScope);

        static::creating(function (Model $model): void {
            $context = app(TenantContext::class);

            if ($context->has()) {
                $model->setAttribute('tenant_id', $model->getAttribute('tenant_id') ?? $context->id());

                if ($model->getAttribute('tenant_id') !== $context->id()) {
                    throw new LogicException('Cannot create a record for a tenant other than the active one.');
                }

                return;
            }

            if (! TenantScope::isPlatformConnection($model)) {
                throw new MissingTenantContext;
            }
        });
    }

    /**
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
