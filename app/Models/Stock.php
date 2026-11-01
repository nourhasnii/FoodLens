<?php

namespace App\Models;

use App\Support\BatchAttributes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Stock extends Model
{
    /** @use HasFactory<\Database\Factories\StockFactory> */
    use HasFactory;

    protected $fillable = [
        'site_id', 'product_id', 'batch_id', 'quantity', 'min_threshold', 'last_movement_at',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'float',
            'min_threshold' => 'float',
            'last_movement_at' => 'datetime',
        ];
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    /** Filtre sur un lot précis, ou sur les lignes "sans lot" si $batchId est null. */
    public function scopeForBatch(Builder $query, ?int $batchId): void
    {
        $batchId ? $query->where('batch_id', $batchId) : $query->whereNull('batch_id');
    }

    /** Un lot rappelé ou périmé n'est plus vendable : il reste en stock physique mais n'est pas disponible. */
    public function isUnusable(): bool
    {
        return $this->batch !== null
            && (BatchAttributes::isBlocked($this->batch) || BatchAttributes::isExpired($this->batch));
    }

    public function availableQuantity(): float
    {
        return $this->isUnusable() ? 0.0 : (float) $this->quantity;
    }

    /**
     * État d'affichage de la ligne. Le seuil étant défini par produit et par site,
     * l'information "sous le seuil" est calculée au niveau agrégé par StockService.
     */
    public function state(bool $productBelowThreshold = false): string
    {
        if ($this->batch) {
            if (BatchAttributes::isBlocked($this->batch)) {
                return 'recalled';
            }

            $days = BatchAttributes::daysToEffectiveExpiry($this->batch); // DLC réelle (chaîne du froid)

            if ($days !== null && $days < 0) {
                return 'expired';
            }

            if ($days !== null && $days <= (int) config('stock.expiring_soon_days', 7)) {
                return 'expiring';
            }
        }

        return $productBelowThreshold ? 'low' : 'ok';
    }

    public static function stateLabel(string $state): string
    {
        return match ($state) {
            'recalled' => 'Lot rappelé',
            'expired' => 'DLC dépassée',
            'expiring' => 'DLC proche',
            'low' => 'Sous le seuil',
            default => 'Normal',
        };
    }
}
