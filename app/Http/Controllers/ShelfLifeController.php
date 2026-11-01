<?php

namespace App\Http\Controllers;

use App\Http\Requests\ShelfLifeDecisionRequest;
use App\Models\Batch;
use App\Models\ShelfLifeAssessment;
use App\Models\Stock;
use App\Models\TransportCondition;
use App\Services\ShelfLife\ShelfLifePredictor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Écrans « DLC dynamique » : vraie durée de vie des lots d'après les températures du transport. */
class ShelfLifeController extends Controller
{
    public function __construct(private readonly ShelfLifePredictor $predictor) {}

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'scope' => ['nullable', 'in:stock,all'],
            'risk' => ['nullable', 'in:'.implode(',', array_keys(ShelfLifePredictor::RISK_LABELS))],
        ]);
        $scope = $filters['scope'] ?? 'stock';

        // Lots présents dans les stocks du module, ou tous les lots suivis par la chaîne du froid
        $stockByBatch = Stock::query()->with('site:id,name')->whereNotNull('batch_id')->where('quantity', '>', 0)->get()->groupBy('batch_id');
        $batchIds = $scope === 'all'
            ? $stockByBatch->keys()->merge(TransportCondition::query()->distinct()->pluck('batch_id'))->unique()
            : $stockByBatch->keys();

        $batches = Batch::query()->with('product')->whereIn('id', $batchIds)->get();
        $readings = TransportCondition::query()->whereIn('batch_id', $batches->modelKeys())->orderBy('recorded_at')->get()->groupBy('batch_id');

        $rows = $batches
            ->map(function (Batch $batch) use ($readings, $stockByBatch) {
                $prediction = $this->predictor->predict($batch, $readings[$batch->getKey()] ?? collect());
                if (! $prediction) {
                    return null;
                }
                $lines = $stockByBatch[$batch->getKey()] ?? collect();

                return $prediction + [
                    'units' => (float) $lines->sum('quantity'),
                    'sites' => $lines->map(fn (Stock $l) => $l->site?->name)->filter()->unique()->values()->all(),
                ];
            })
            ->filter()
            ->sortBy([
                fn ($a, $b) => ShelfLifePredictor::RISK_ORDER[$a['risk']] <=> ShelfLifePredictor::RISK_ORDER[$b['risk']],
                fn ($a, $b) => $b['lost_hours'] <=> $a['lost_hours'],
            ])
            ->values();

        $visible = isset($filters['risk']) ? $rows->where('risk', $filters['risk'])->values() : $rows;

        return view('shelf-life.index', [
            'rows' => $visible,
            'scope' => $scope,
            'filters' => $filters,
            'counts' => [
                'lots' => $rows->count(),
                'monitored' => $rows->where('readings', '>', 0)->count(),
                'at_risk' => $rows->whereIn('risk', [ShelfLifePredictor::RISK_ATTENTION, ShelfLifePredictor::RISK_CRITICAL])->count(),
                'days_lost' => round($rows->whereIn('risk', [ShelfLifePredictor::RISK_ATTENTION, ShelfLifePredictor::RISK_CRITICAL])->sum('days_lost'), 1),
                'units_at_risk' => $rows->whereIn('risk', [ShelfLifePredictor::RISK_ATTENTION, ShelfLifePredictor::RISK_CRITICAL])->sum('units'),
            ],
            'profiles' => config('stock.shelf_life.profiles'),
        ]);
    }

    public function show(Batch $batch): View
    {
        $batch->load('product');
        $prediction = $this->predictor->predict($batch);
        abort_unless($prediction, 404, 'Ce lot n’a pas de DLC.');

        return view('shelf-life.show', [
            'batch' => $batch,
            'p' => $prediction,
            'lines' => Stock::query()->with('site:id,name,city')->where('batch_id', $batch->getKey())->where('quantity', '>', 0)->orderByDesc('quantity')->get(),
            'history' => ShelfLifeAssessment::query()->with('user:id,name')->where('batch_id', $batch->getKey())->latest('id')->get(),
        ]);
    }

    /** Un responsable valide la DLC réelle (utilisée par le module) ou garde la DLC imprimée (avec justification). */
    public function decide(ShelfLifeDecisionRequest $request, Batch $batch): RedirectResponse
    {
        $p = $this->predictor->predict($batch->load('product'));
        abort_unless($p, 404);

        ShelfLifeAssessment::create([
            'batch_id' => $batch->getKey(),
            'printed_expiry' => $p['printed'],
            'predicted_expiry' => $p['predicted'],
            'predicted_earliest' => $p['earliest'],
            'predicted_latest' => $p['latest'],
            'days_lost' => $p['days_lost'],
            'risk' => $p['risk'],
            'decision' => $request->validated('decision'),
            'note' => $request->validated('note'),
            'model' => $p['model'] + ['profile' => $p['profile'], 'lost_hours' => $p['lost_hours'], 'readings' => $p['readings']],
            'user_id' => $request->user()?->getKey(),
        ]);

        return redirect()->route('shelf-life.show', $batch)->with('success', $request->validated('decision') === ShelfLifeAssessment::ACCEPTED
            ? 'DLC réelle validée : le module l’utilise pour la rotation des stocks, l’optimisation et l’anti-gaspillage.'
            : 'DLC imprimée conservée : votre justification est enregistrée.');
    }
}
