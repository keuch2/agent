<?php

namespace App\Domain\Audit;

use App\Domain\Audit\Models\AuditLog;
use App\Domain\Tenancy\Support\SupportMode;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

final class AuditLogger
{
    private const array REDACTED = ['password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes', 'access_token'];

    public function __construct(private readonly Request $request) {}

    /**
     * @param  array<string, mixed>  $old
     * @param  array<string, mixed>  $new
     */
    public function record(string $action, ?Model $subject = null, array $old = [], array $new = [], ?int $tenantId = null, bool $supportMode = false): AuditLog
    {
        $log = new AuditLog([
            'user_id' => $this->request->user()?->getAuthIdentifier(),
            'action' => $action,
            'old_values' => Arr::except($old, self::REDACTED) ?: null,
            'new_values' => Arr::except($new, self::REDACTED) ?: null,
            'ip_address' => $this->request->ip(),
            'support_mode' => $supportMode || $this->request->attributes->getBoolean(SupportMode::REQUEST_ATTRIBUTE),
        ]);

        $log->tenant_id = $tenantId;
        $log->subject()->associate($subject);
        $log->save();

        return $log;
    }

    /**
     * Record an update with only the attributes that actually changed.
     */
    public function recordChanges(string $action, Model $subject, ?int $tenantId = null): ?AuditLog
    {
        $changes = Arr::except($subject->getChanges(), ['updated_at']);

        if ($changes === []) {
            return null;
        }

        $old = Arr::only($subject->getPrevious(), array_keys($changes));

        return $this->record($action, $subject, $old, $changes, $tenantId);
    }
}
