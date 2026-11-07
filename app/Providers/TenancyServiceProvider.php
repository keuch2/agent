<?php

namespace App\Providers;

use App\Domain\Tenancy\TenantContext;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class TenancyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(TenantContext::class);
    }

    public function boot(): void
    {
        // Queued jobs carry the dispatcher's tenant in Context; restore it (or
        // clear a stale one left by the previous job on this worker).
        Context::hydrated(fn () => $this->app->make(TenantContext::class)->restoreFromContext());

        // Sync jobs run inside the dispatching request, which keeps its tenant.
        Event::listen(
            [JobProcessed::class, JobFailed::class],
            function (JobProcessed|JobFailed $event): void {
                if ($event->connectionName !== 'sync') {
                    $this->app->make(TenantContext::class)->clear();
                }
            },
        );
    }
}
