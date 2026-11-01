<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * NutriMind — évaluation de risque persistée et explicable.
 *
 * Conserve l'historique des évaluations d'une entité (produit, lot, fournisseur)
 * sans jamais écraser les précédentes, pour permettre la comparaison avant/après
 * une action corrective.
 */
class RiskAssessment extends Model
{
    use HasFactory;

    public const SEVERITIES = ['info', 'warning', 'critical'];

    public const KINDS = ['risk', 'missing'];

    protected $fillable = [
        'assessable_type',
        'assessable_id',
        'entity_label',
        'score',
        'severity',
        'findings',
        'evidence',
        'recommendation',
        'data_completeness',
        'critical_override',
        'rule_set_version',
        'is_simulation',
        'baseline_id',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'score' => 'decimal:2',
            'findings' => 'array',
            'evidence' => 'array',
            'data_completeness' => 'decimal:2',
            'critical_override' => 'boolean',
            'is_simulation' => 'boolean',
        ];
    }

    public function assessable()
    {
        return $this->morphTo();
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function baseline()
    {
        return $this->belongsTo(self::class, 'baseline_id');
    }

    /** Évaluations réelles (hors simulations). */
    public function scopeReal(Builder $query): Builder
    {
        return $query->where('is_simulation', false);
    }

    /** @return array<int, array<string, mixed>> Constatations de risque confirmées. */
    public function riskFindings(): array
    {
        return collect($this->findings ?? [])->where('kind', 'risk')->values()->all();
    }

    /** @return array<int, array<string, mixed>> Informations manquantes (hors score). */
    public function missingFindings(): array
    {
        return collect($this->findings ?? [])->where('kind', 'missing')->values()->all();
    }

    /** @return Collection<int, string> Sources de preuve citées. */
    public function sources(): Collection
    {
        return collect($this->evidence ?? [])->pluck('source_label')->filter()->unique()->values();
    }
}
