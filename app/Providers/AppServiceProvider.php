<?php

namespace App\Providers;

use App\Domain\Billing\Models\Plan;
use App\Domain\Billing\Policies\PlanPolicy;
use App\Domain\Platform\Mail\PlatformMailTransport;
use App\Domain\Tenancy\Models\Tenant;
use App\Domain\Tenancy\Policies\TenantPolicy;
use App\Domain\Tenancy\Support\SupportMode;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();

        // A superadmin in support mode acts with full rights inside that tenant.
        Gate::before(fn (User $user): ?bool => $user->is_superadmin && request()->attributes->getBoolean(SupportMode::REQUEST_ATTRIBUTE) ? true : null);

        Mail::extend('platform', fn () => $this->app->make(PlatformMailTransport::class));

        Gate::policy(Tenant::class, TenantPolicy::class);
        Gate::policy(Plan::class, PlanPolicy::class);

        RateLimiter::for('meta-webhooks', fn (Request $request) => Limit::perMinute(1200)->by($request->ip()));
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
