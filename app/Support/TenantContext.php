<?php

namespace App\Support;

use App\Models\Tenant;
use RuntimeException;

class TenantContext
{
    protected ?Tenant $tenant = null;

    public function set(Tenant $tenant): void
    {
        $this->tenant = $tenant;
    }

    public function get(): ?Tenant
    {
        return $this->tenant;
    }

    public function id(): ?int
    {
        return $this->tenant?->id;
    }

    public function has(): bool
    {
        return $this->tenant !== null;
    }

    public function requireTenant(): Tenant
    {
        if (! $this->tenant) {
            throw new RuntimeException('No tenant has been resolved for the current request.');
        }

        return $this->tenant;
    }

    public function clear(): void
    {
        $this->tenant = null;
    }
}