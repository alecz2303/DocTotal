<?php

namespace App\Models;

use App\Models\Scopes\TenantScope;
use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use RuntimeException;

class AuditEvent extends Model
{
    private bool $allowTenantless = false;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'action',
        'auditable_type',
        'auditable_id',
        'description',
        'ip_address',
        'user_agent',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    public static function createGlobal(array $attributes): self
    {
        $event = new self();
        $event->allowTenantless = true;
        $event->fill($attributes);
        $event->save();

        return $event;
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }

    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScope());

        static::creating(function (AuditEvent $event): void {
            if ($event->tenant_id !== null || $event->allowTenantless) {
                return;
            }

            $event->tenant_id = app(TenantContext::class)
                ->requireTenant()
                ->id;
        });

        static::updating(function (): void {
            throw new RuntimeException(
                'Audit events are immutable and cannot be updated.'
            );
        });

        static::deleting(function (): void {
            throw new RuntimeException(
                'Audit events are immutable and cannot be deleted.'
            );
        });
    }
}
