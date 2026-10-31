<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Une simulation du jumeau numérique : scénario « Et si ? » + résultats sans IA / avec IA. */
class StockSimulation extends Model
{
    /** @use HasFactory<\Database\Factories\StockSimulationFactory> */
    use HasFactory;

    public const PRESETS = [
        'normal' => 'Semaine normale',
        'demand_peak' => 'Pic de demande (Ramadan)',
        'truck_breakdown' => 'Panne de camion',
        'store_closed' => 'Magasin fermé',
        'cold_chain' => 'Rupture de la chaîne du froid',
        'custom' => 'Scénario personnalisé',
    ];

    protected $fillable = [
        'name', 'preset', 'scenario', 'horizon_days', 'start_date',
        'baseline_wasted', 'ai_wasted', 'baseline_unmet', 'ai_unmet', 'ai_donated',
        'results', 'user_id',
    ];

    protected function casts(): array
    {
        return [
            'scenario' => 'array',
            'results' => 'array',
            'start_date' => 'date',
            'horizon_days' => 'integer',
            'baseline_wasted' => 'float',
            'ai_wasted' => 'float',
            'baseline_unmet' => 'float',
            'ai_unmet' => 'float',
            'ai_donated' => 'float',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function presetLabel(): string
    {
        return self::PRESETS[$this->preset] ?? self::PRESETS['custom'];
    }

    /** Baisse du gaspillage grâce à l'IA, en % (null si rien n'était gaspillé sans IA). */
    public function wasteReduction(): ?int
    {
        return $this->baseline_wasted > 0
            ? (int) round(100 * ($this->baseline_wasted - $this->ai_wasted) / $this->baseline_wasted)
            : null;
    }
}
