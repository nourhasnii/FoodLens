<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Inventaire par photo : comptage proposé par l'IA de vision, validé par un humain. */
class PhotoInventory extends Model
{
    /** @use HasFactory<\Database\Factories\PhotoInventoryFactory> */
    use HasFactory;

    public const PENDING = 'pending';

    public const ANALYZED = 'analyzed';

    public const FAILED = 'failed';

    public const APPLIED = 'applied';

    protected $fillable = [
        'site_id', 'photo_path', 'status', 'ai_model', 'items', 'unmatched', 'ai_notes', 'error',
        'duration_ms', 'adjustments_count', 'user_id', 'applied_by', 'applied_at',
    ];

    protected function casts(): array
    {
        return [
            'items' => 'array',
            'unmatched' => 'array',
            'applied_at' => 'datetime',
            'duration_ms' => 'integer',
            'adjustments_count' => 'integer',
        ];
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function applier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'applied_by');
    }

    public function isApplied(): bool
    {
        return $this->status === self::APPLIED;
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::ANALYZED => 'À valider',
            self::FAILED => 'Saisie manuelle',
            self::APPLIED => 'Appliqué',
            default => 'En attente',
        };
    }

    public function statusTag(): string
    {
        return match ($this->status) {
            self::ANALYZED => 'tag-pending',
            self::FAILED => 'tag-adjustment',
            self::APPLIED => 'tag-accepted',
            default => '',
        };
    }

    /** Nombre de produits dont le comptage diffère du stock enregistré. */
    public function discrepancies(): int
    {
        return collect($this->items ?? [])
            ->filter(fn (array $i) => ($i['final_qty'] ?? $i['ai_count'] ?? null) !== null
                && abs((float) ($i['final_qty'] ?? $i['ai_count']) - (float) $i['system_qty']) >= 0.01)
            ->count();
    }
}
