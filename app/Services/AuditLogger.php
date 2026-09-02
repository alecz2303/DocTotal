<?php

namespace App\Services;

use App\Models\AuditEvent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Throwable;

class AuditLogger
{
    public function log(
        string $action,
        ?Model $auditable = null,
        ?string $description = null,
        array $metadata = [],
    ): AuditEvent {
        return AuditEvent::create([
            'user_id' => Auth::id(),
            'action' => $action,
            'auditable_type' => $auditable?->getMorphClass(),
            'auditable_id' => $auditable?->getKey(),
            'description' => $description,
            'ip_address' => $this->ipAddress(),
            'user_agent' => $this->userAgent(),
            'metadata' => $this->sanitizeMetadata($metadata),
        ]);
    }

    public function safeLog(
        string $action,
        ?Model $auditable = null,
        ?string $description = null,
        array $metadata = [],
    ): ?AuditEvent {
        try {
            return $this->log(
                action: $action,
                auditable: $auditable,
                description: $description,
                metadata: $metadata,
            );
        } catch (Throwable $exception) {
            Log::error(
                'Audit event could not be recorded.',
                [
                    'action' => $action,
                    'auditable_type' =>
                    $auditable?->getMorphClass(),
                    'auditable_id' =>
                    $auditable?->getKey(),
                    'exception' =>
                    $exception->getMessage(),
                ]
            );

            return null;
        }
    }

    private function ipAddress(): ?string
    {
        if (! app()->bound('request')) {
            return null;
        }

        return request()->ip();
    }

    private function userAgent(): ?string
    {
        if (! app()->bound('request')) {
            return null;
        }

        return request()->userAgent();
    }

    private function sanitizeMetadata(array $metadata): array
    {
        $sensitiveFragments = [
            'password',
            'token',
            'authorization',
            'cookie',
            'secret',
            'api_key',
        ];

        foreach ($metadata as $key => $value) {
            if (is_string($key)) {
                $normalizedKey = strtolower($key);

                foreach ($sensitiveFragments as $fragment) {
                    if (str_contains($normalizedKey, $fragment)) {
                        $metadata[$key] = '[REDACTED]';

                        continue 2;
                    }
                }
            }

            if (is_array($value)) {
                $metadata[$key] = $this->sanitizeMetadata($value);
            }
        }

        return $metadata;
    }
}
