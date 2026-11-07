<?php

namespace App\Domain\Tenancy\Scopes;

use App\Domain\Tenancy\Exceptions\MissingTenantContext;
use App\Domain\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Filters by the active tenant. Without one, the query is only allowed on the
 * platform (admin) connection; anywhere else it fails closed.
 *
 * @template TModel of Model
 *
 * @implements Scope<TModel>
 */
final class TenantScope implements Scope
{
    /**
     * @param  bool  $strict  When false, a missing context means "no filter"
     *                        (only for the identity table, read before login).
     */
    public function __construct(private readonly bool $strict = true) {}

    /**
     * @param  Builder<covariant TModel>  $builder
     * @param  TModel  $model
     */
    public function apply(Builder $builder, Model $model): void
    {
        $context = app(TenantContext::class);

        if ($context->has()) {
            $builder->where($model->qualifyColumn('tenant_id'), $context->id());

            return;
        }

        if ($this->strict && ! self::isPlatformConnection($model)) {
            throw new MissingTenantContext;
        }
    }

    public static function isPlatformConnection(Model $model): bool
    {
        return ($model->getConnectionName() ?? config('database.default')) === TenantContext::ADMIN_CONNECTION;
    }
}
