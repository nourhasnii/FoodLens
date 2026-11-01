<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Alert extends Model
{
    use HasFactory;

    public const TYPES = ['temperature', 'humidity', 'duration', 'other'];

    /** Sources détectées automatiquement par le NutriAlert Hub. */
    public const RISK_TYPES = ['certification', 'ocr', 'recall', 'quality', 'coverage'];

    public const SEVERITIES = ['info', 'warning', 'critical'];
    public const STATUSES = ['open', 'acknowledged', 'resolved', 'ignored'];

    protected $fillable = [
        'batch_id',
        'transport_condition_id',
        'type',
        'severity',
        'title',
        'message',
        'status',
        'risk_score',
        'dedup_key',
        'alertable_type',
        'alertable_id',
        'resolved_at',
        'resolved_by',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'risk_score' => 'decimal:2',
            'resolved_at' => 'datetime',
        ];
    }

    public function batch()
    {
        return $this->belongsTo(Batch::class);
    }

    /**
     * Enregistrement source du risque (Certification, OcrCheck, QualityCheck, Batch, Product).
     */
    public function alertable()
    {
        return $this->morphTo();
    }

    public function transportCondition()
    {
        return $this->belongsTo(TransportCondition::class);
    }

    public function resolver()
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', 'open');
    }
}
