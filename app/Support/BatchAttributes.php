<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;

/**
 * Lecture des attributs du modèle Batch (module Lots) via la configuration,
 * pour ne pas dépendre des noms de colonnes exacts choisis par l'autre module.
 */
final class BatchAttributes
{
    public static function code(?Model $batch): string
    {
        if (! $batch) {
            return '—';
        }

        return (string) ($batch->getAttribute(config('stock.batch.code_column', 'code')) ?? '#'.$batch->getKey());
    }

    public static function expiryDate(?Model $batch): ?CarbonImmutable
    {
        if (! $batch) {
            return null;
        }

        $value = $batch->getAttribute(config('stock.batch.expiry_column', 'expiry_date'));

        return $value ? CarbonImmutable::parse($value)->startOfDay() : null;
    }

    /** Jours restants avant la DLC (négatif si dépassée), null si inconnue. */
    public static function daysToExpiry(?Model $batch): ?int
    {
        $expiry = self::expiryDate($batch);

        return $expiry ? (int) round(CarbonImmutable::today()->diffInDays($expiry, false)) : null;
    }

    /**
     * DLC utilisée pour les DÉCISIONS du module (FEFO, optimisation, anti-gaspillage, jumeau numérique) :
     * la DLC réelle prédite d'après les températures du transport, sauf si un responsable l'a rejetée.
     * Sans relevé de température : la DLC imprimée.
     */
    public static function effectiveExpiryDate(?Model $batch): ?CarbonImmutable
    {
        if (! $batch instanceof \App\Models\Batch) {
            return self::expiryDate($batch);
        }

        return app(\App\Services\ShelfLife\ShelfLifePredictor::class)->effectiveExpiry($batch);
    }

    /** Jours restants avant la DLC réelle (négatif si dépassée), null si inconnue. */
    public static function daysToEffectiveExpiry(?Model $batch): ?int
    {
        $expiry = self::effectiveExpiryDate($batch);

        return $expiry ? (int) round(CarbonImmutable::today()->diffInDays($expiry, false)) : null;
    }

    public static function isExpired(?Model $batch): bool
    {
        $days = self::daysToExpiry($batch);

        return $days !== null && $days < 0;
    }

    public static function status(?Model $batch): ?string
    {
        if (! $batch) {
            return null;
        }

        $status = $batch->getAttribute(config('stock.batch.status_column', 'status'));

        return $status instanceof \BackedEnum ? (string) $status->value : $status;
    }

    public static function isBlocked(?Model $batch): bool
    {
        return in_array(self::status($batch), config('stock.batch.blocked_statuses', ['recalled']), true);
    }

    public static function unitWeightKg(?Model $product): float
    {
        $column = config('stock.product.unit_weight_column');
        $weight = ($product && $column) ? (float) $product->getAttribute($column) : 0.0;

        return $weight > 0 ? $weight : (float) config('stock.product.default_unit_weight_kg', 1.0);
    }
}
