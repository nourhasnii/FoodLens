<?php

namespace App\Services;

use App\Models\Alert;
use App\Models\Batch;
use App\Models\Certification;
use App\Models\OcrCheck;
use App\Models\Product;
use App\Models\QualityCheck;
use App\Models\User;
use App\Notifications\RiskAlertNotification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;

/**
 * NutriAlert Hub — détection automatique des risques et synchronisation des alertes.
 *
 * Chaque règle produit un jeu d'alertes "souhaitées" indexées par une clé déterministe
 * `type:id`. La réconciliation :
 *  - crée l'alerte si elle n'existe pas,
 *  - met à jour la même alerte (jamais de doublon) si le risque évolue,
 *  - la résout si le risque a disparu.
 */
class NutriAlertService
{
    /**
     * Exécute toutes les règles de détection et synchronise les alertes.
     *
     * @return array{created:int, updated:int, resolved:int, notified:int}
     */
    public function sync(): array
    {
        $stats = ['created' => 0, 'updated' => 0, 'resolved' => 0, 'notified' => 0];

        $detectors = [
            'certification' => fn () => $this->detectCertifications(),
            'ocr' => fn () => $this->detectOcrInconsistencies(),
            'recall' => fn () => $this->detectRecalledLots(),
            'quality' => fn () => $this->detectLowQuality(),
            'coverage' => fn () => $this->detectMissingCertification(),
        ];

        foreach ($detectors as $type => $detector) {
            $stats = $this->reconcile($type, $detector(), $stats);
        }

        return $stats;
    }

    /**
     * Score de priorité déterministe (0-100) :
     * sévérité + proximité d'expiration + impact métier (produits/lots affectés).
     */
    public static function priorityScore(string $severity, ?int $daysLeft = null, int $impact = 0, bool $recall = false): float
    {
        $base = ['info' => 20.0, 'warning' => 45.0, 'critical' => 70.0][$severity] ?? 20.0;

        $urgency = 0.0;
        if ($recall) {
            $urgency = 20.0;
        } elseif ($daysLeft !== null) {
            $urgency = match (true) {
                $daysLeft <= 0 => 30.0,
                $daysLeft <= 3 => 22.0,
                $daysLeft <= 7 => 15.0,
                $daysLeft <= 15 => 8.0,
                default => 0.0,
            };
        }

        $impactScore = min(10.0, $impact * 2.0);

        return round(min(100.0, $base + $urgency + $impactScore), 2);
    }

    // ------------------------------------------------------------------
    // Règles de détection
    // ------------------------------------------------------------------

    /** Certifications expirées ou expirant dans 15 / 7 / 3 jours. */
    public function detectCertifications(): array
    {
        $maxDays = (int) max(config('nutrialert.certification_warning_days', [15, 7, 3]));
        $alerts = [];

        Certification::with('product')
            ->whereNotNull('expires_at')
            ->where('status', '!=', Certification::STATUS_PENDING)
            ->get()
            ->each(function (Certification $certification) use (&$alerts, $maxDays) {
                $daysLeft = $this->daysUntil($certification->expires_at);

                if ($daysLeft > $maxDays) {
                    return;
                }

                $severity = match (true) {
                    $daysLeft <= 3 => 'critical',
                    $daysLeft <= 7 => 'warning',
                    default => 'info',
                };

                $impact = $certification->product_id
                    ? Batch::where('product_id', $certification->product_id)->where('status', 'active')->count()
                    : 0;

                $productName = $certification->product?->name;

                $alerts[$this->key('certification', $certification->id)] = [
                    'batch_id' => null,
                    'transport_condition_id' => null,
                    'type' => 'certification',
                    'severity' => $severity,
                    'title' => $daysLeft <= 0
                        ? sprintf('Certification expirée : %s', $certification->name)
                        : sprintf('Certification à renouveler dans %d jour(s) : %s', $daysLeft, $certification->name),
                    'message' => $daysLeft <= 0
                        ? sprintf(
                            'La certification « %s » (%s) a expiré le %s%s. Aucun lot ne peut plus être justifié par cette certification.',
                            $certification->name,
                            $certification->certificate_number ?: 'sans numéro',
                            optional($certification->expires_at)->format('d/m/Y'),
                            $productName ? sprintf(' pour le produit « %s »', $productName) : ''
                        )
                        : sprintf(
                            'La certification « %s » (%s) expire le %s, dans %d jour(s)%s.',
                            $certification->name,
                            $certification->certificate_number ?: 'sans numéro',
                            optional($certification->expires_at)->format('d/m/Y'),
                            $daysLeft,
                            $productName ? sprintf(' — produit « %s »', $productName) : ''
                        ),
                    'risk_score' => self::priorityScore($severity, $daysLeft, $impact),
                    'alertable_type' => Certification::class,
                    'alertable_id' => $certification->id,
                ];
            });

        return $alerts;
    }

    /** Incohérences OCR entre données extraites et données déclarées. */
    public function detectOcrInconsistencies(): array
    {
        $threshold = (float) config('nutrialert.ocr_threshold', 40);
        $criticalThreshold = (float) config('nutrialert.ocr_critical_threshold', 70);
        $alerts = [];

        OcrCheck::with('product')
            ->where('status', 'completed')
            ->whereNotNull('inconsistency_score')
            ->get()
            ->each(function (OcrCheck $check) use (&$alerts, $threshold, $criticalThreshold) {
                $score = (float) $check->inconsistency_score;
                $hasCriticalField = collect($check->mismatches ?? [])
                    ->contains(fn ($mismatch) => ($mismatch['severity'] ?? null) === 'critical');

                if ($score < $threshold && ! $hasCriticalField) {
                    return;
                }

                $severity = ($score >= $criticalThreshold || $hasCriticalField) ? 'critical' : 'warning';
                $impact = $check->product_id
                    ? Batch::where('product_id', $check->product_id)->where('status', 'active')->count()
                    : 0;

                $alerts[$this->key('ocr', $check->id)] = [
                    'batch_id' => $check->batch_id,
                    'transport_condition_id' => null,
                    'type' => 'ocr',
                    'severity' => $severity,
                    'title' => 'Incohérence OCR sur une étiquette',
                    'message' => sprintf(
                        'Le contrôle OCR%s présente un score d’incohérence de %.0f/100 entre les données extraites de l’étiquette et les données déclarées.',
                        $check->batch?->lot_number ? sprintf(' du lot %s', $check->batch->lot_number) : sprintf(' n°%d', $check->id),
                        $score
                    ),
                    'risk_score' => self::priorityScore($severity, null, $impact),
                    'alertable_type' => OcrCheck::class,
                    'alertable_id' => $check->id,
                ];
            });

        return $alerts;
    }

    /** Lots rappelés (détection seule — aucun rappel déclenché automatiquement). */
    public function detectRecalledLots(): array
    {
        $alerts = [];

        Batch::with('product')
            ->where('status', 'recalled')
            ->get()
            ->each(function (Batch $batch) use (&$alerts) {
                $alerts[$this->key('recall', $batch->id)] = [
                    'batch_id' => $batch->id,
                    'transport_condition_id' => null,
                    'type' => 'recall',
                    'severity' => 'critical',
                    'title' => sprintf('Lot rappelé : %s', $batch->lot_number),
                    'message' => sprintf(
                        'Le lot %s%s est au statut « rappelé ». Vérifiez la procédure de rappel et informez les distributeurs concernés.',
                        $batch->lot_number,
                        $batch->product?->name ? sprintf(' (produit « %s »)', $batch->product->name) : ''
                    ),
                    'risk_score' => self::priorityScore('critical', null, 1, recall: true),
                    'alertable_type' => Batch::class,
                    'alertable_id' => $batch->id,
                ];
            });

        return $alerts;
    }

    /** Lots analysés dont le score qualité est inférieur au seuil. */
    public function detectLowQuality(): array
    {
        $threshold = (float) config('nutrialert.low_quality_threshold', 50);
        $criticalThreshold = (float) config('nutrialert.low_quality_critical_threshold', 30);
        $alerts = [];

        QualityCheck::with('product')
            ->where('status', 'completed')
            ->whereNotNull('quality_score')
            ->where('quality_score', '<', $threshold)
            ->get()
            ->each(function (QualityCheck $check) use (&$alerts, $criticalThreshold) {
                $score = (float) $check->quality_score;
                $severity = $score < $criticalThreshold ? 'critical' : 'warning';
                $impact = $check->product_id
                    ? Batch::where('product_id', $check->product_id)->where('status', 'active')->count()
                    : 0;

                $alerts[$this->key('quality', $check->id)] = [
                    'batch_id' => $check->batch_id,
                    'transport_condition_id' => null,
                    'type' => 'quality',
                    'severity' => $severity,
                    'title' => sprintf('Score qualité faible : %.0f/100', $score),
                    'message' => sprintf(
                        'L’analyse qualité%s obtient un score de %.0f/100 (décision : %s). Une revue est recommandée avant distribution.',
                        $check->product?->name ? sprintf(' du produit « %s »', $check->product->name) : sprintf(' n°%d', $check->id),
                        $score,
                        $check->decision ?: 'non définie'
                    ),
                    'risk_score' => self::priorityScore($severity, null, $impact),
                    'alertable_type' => QualityCheck::class,
                    'alertable_id' => $check->id,
                ];
            });

        return $alerts;
    }

    /** Produits ayant des lots actifs mais aucune certification valide. */
    public function detectMissingCertification(): array
    {
        $alerts = [];

        Product::query()
            ->whereHas('batches', fn ($query) => $query->where('status', 'active'))
            ->whereDoesntHave('certifications', fn ($query) => $query
                ->where('status', '!=', Certification::STATUS_PENDING)
                ->whereDate('expires_at', '>', today()))
            ->withCount(['batches' => fn ($query) => $query->where('status', 'active')])
            ->get()
            ->each(function (Product $product) use (&$alerts) {
                $impact = (int) $product->batches_count;

                $alerts[$this->key('coverage', $product->id)] = [
                    'batch_id' => null,
                    'transport_condition_id' => null,
                    'type' => 'coverage',
                    'severity' => 'warning',
                    'title' => sprintf('Produit actif sans certification valide : %s', $product->name),
                    'message' => sprintf(
                        'Le produit « %s » possède %d lot(s) actif(s) mais aucune certification valide. Régularisez la certification pour maintenir la traçabilité.',
                        $product->name,
                        $impact
                    ),
                    'risk_score' => self::priorityScore('warning', null, $impact),
                    'alertable_type' => Product::class,
                    'alertable_id' => $product->id,
                ];
            });

        return $alerts;
    }

    // ------------------------------------------------------------------
    // Réconciliation (upsert + résolution + notification)
    // ------------------------------------------------------------------

    /**
     * @param  array<string, array<string, mixed>>  $desired
     * @param  array{created:int, updated:int, resolved:int, notified:int}  $stats
     * @return array{created:int, updated:int, resolved:int, notified:int}
     */
    private function reconcile(string $type, array $desired, array $stats): array
    {
        $existing = Alert::query()
            ->where('dedup_key', 'like', $type.':%')
            ->get()
            ->keyBy('dedup_key');

        foreach ($desired as $key => $attributes) {
            /** @var Alert|null $alert */
            $alert = $existing->get($key);
            [$alert, $created, $shouldNotify] = $this->upsert($key, $attributes, $alert);

            $stats[$created ? 'created' : 'updated']++;

            if ($shouldNotify) {
                $stats['notified'] += $this->notifyRecipients($alert);
            }

            $existing->forget($key);
        }

        // Alertes dont le risque a disparu : on les résout.
        foreach ($existing as $alert) {
            if ($alert->status === 'resolved') {
                continue;
            }

            $alert->update(['status' => 'resolved', 'resolved_at' => now(), 'resolved_by' => null]);
            $this->clearNotifications($alert);
            $stats['resolved']++;
        }

        return $stats;
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array{0: Alert, 1: bool, 2: bool} [alerte, créée, doit notifier]
     */
    private function upsert(string $key, array $attributes, ?Alert $alert): array
    {
        $attributes['dedup_key'] = $key;
        $attributes['created_by'] = null;

        if ($alert === null) {
            $alert = Alert::create($attributes + ['status' => 'open']);

            return [$alert, true, true];
        }

        $previousSeverity = $alert->severity;
        $previousStatus = $alert->status;
        $wasClosed = in_array($previousStatus, ['resolved', 'ignored'], true);

        $attributes['status'] = $wasClosed ? 'open' : $previousStatus;
        $attributes['resolved_at'] = null;
        $attributes['resolved_by'] = null;

        $alert->update($attributes);

        // On ne renotifie que si le risque change matériellement : nouveau risque,
        // changement de sévérité, ou réouverture d'une alerte fermée. Le statut de
        // lecture des notifications existantes est ainsi préservé.
        $shouldNotify = $wasClosed || $previousSeverity !== $alert->severity;

        return [$alert, false, $shouldNotify];
    }

    private function notifyRecipients(Alert $alert): int
    {
        $recipients = $this->recipients($alert);

        if ($recipients->isEmpty()) {
            return 0;
        }

        Notification::send($recipients, new RiskAlertNotification($alert));

        return $recipients->count();
    }

    /** @return Collection<int, User> */
    private function recipients(Alert $alert): Collection
    {
        // whereHas (et non User::role) : ne lève pas d'exception si le rôle
        // « admin » n'existe pas encore (ex. base fraîchement migrée).
        $recipients = User::query()
            ->whereHas('roles', fn ($query) => $query->where('name', 'admin'))
            ->get();

        $producer = $this->resolveProduct($alert)?->producer;

        if ($producer && ! $recipients->contains('id', $producer->id)) {
            $recipients->push($producer);
        }

        return $recipients;
    }

    private function resolveProduct(Alert $alert): ?Product
    {
        $alertable = $alert->alertable;

        if ($alertable instanceof Product) {
            return $alertable;
        }

        if ($alertable instanceof Model && method_exists($alertable, 'product')) {
            return $alertable->product;
        }

        return $alert->batch?->product;
    }

    private function clearNotifications(Alert $alert): void
    {
        \Illuminate\Notifications\DatabaseNotification::query()
            ->where('data->alert_id', $alert->id)
            ->delete();
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    /** Action directe associée à une alerte (route + libellé français). */
    public function action(Alert $alert): ?array
    {
        $alertable = $alert->alertable;

        if (! $alertable) {
            return null;
        }

        return match ($alert->type) {
            'certification' => $this->routeAction('admin.certifications.show', $alertable, 'Voir la certification'),
            'ocr' => $this->routeAction('admin.ocr-checks.show', $alertable, 'Voir le contrôle OCR'),
            'quality' => $this->routeAction('admin.quality-checks.show', $alertable, 'Voir l’analyse qualité'),
            'recall' => $this->routeAction('admin.batches.edit', $alertable, 'Gérer le rappel'),
            'coverage' => $this->routeAction('admin.products.show', $alertable, 'Voir le produit'),
            default => null,
        };
    }

    private function routeAction(string $name, Model $alertable, string $label): ?array
    {
        if (! app('router')->has($name)) {
            return null;
        }

        return ['url' => route($name, $alertable), 'label' => $label];
    }

    private function key(string $type, int $id): string
    {
        return $type.':'.$id;
    }

    /** Nombre de jours entiers entre aujourd'hui et une date (négatif si passée). */
    private function daysUntil(\Illuminate\Support\Carbon $date): int
    {
        return (int) floor(($date->copy()->startOfDay()->timestamp - today()->timestamp) / 86400);
    }
}
