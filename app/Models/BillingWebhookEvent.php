<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BillingWebhookEvent extends Model
{
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_PROCESSED = 'processed';
    public const STATUS_IGNORED = 'ignored';
    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'provider',
        'provider_event_id',
        'event_type',
        'status',
        'tenant_id',
        'payment_id',
        'failure_message',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'processed_at' => 'datetime',
        ];
    }
}
