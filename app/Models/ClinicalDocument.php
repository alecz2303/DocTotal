<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClinicalDocument extends Model
{
    use BelongsToTenant, HasUuid;

    public const CATEGORY_GENERAL = 'general';
    public const CATEGORY_LABORATORY = 'laboratory';
    public const CATEGORY_IMAGING = 'imaging';
    public const CATEGORY_OTHER = 'other';

    protected $fillable = [
        'tenant_id',
        'uuid',
        'patient_id',
        'consultation_id',
        'uploaded_by',
        'category',
        'title',
        'document_date',
        'original_name',
        'disk',
        'path',
        'mime_type',
        'size_bytes',
        'notes',
    ];

    protected $attributes = [
        'category' => self::CATEGORY_GENERAL,
    ];

    protected function casts(): array
    {
        return [
            'document_date' => 'date',
            'size_bytes' => 'integer',
        ];
    }

    public static function categories(): array
    {
        return [
            self::CATEGORY_GENERAL,
            self::CATEGORY_LABORATORY,
            self::CATEGORY_IMAGING,
            self::CATEGORY_OTHER,
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function consultation(): BelongsTo
    {
        return $this->belongsTo(Consultation::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'uploaded_by'
        );
    }
}
