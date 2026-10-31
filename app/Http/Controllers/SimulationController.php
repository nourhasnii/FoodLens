<?php

namespace App\Http\Controllers;

use App\Enums\SiteType;
use App\Http\Requests\SimulationRequest;
use App\Models\Product;
use App\Models\Site;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\StockSimulation;
use App\Services\Simulation\DigitalTwinSimulator;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/** Écrans du jumeau numérique « Et si ? ». */
class SimulationController extends Controller
{
    public function __construct(private readonly DigitalTwinSimulator $simulator) {}

    public function index(): View
    {
        $sites = Site::query()->active()->logistics()->orderBy('name')->get(['id', 'name', 'type', 'city']);
        $products = Product::query()->whereIn('id', Stock::query()->where('quantity', '>', 0)->select('product_id'))
            ->orderBy('name')->get(['id', 'name']);

        return view('simulations.index', [
            'sites' => $sites,
            'products' => $products,
            'presets' => $this->presets(),
            'history' => StockSimulation::query()->with('user:id,name')->latest()->paginate(10),
        ]);
    }

    public function store(SimulationRequest $request): RedirectResponse
    {
        $results = $this->simulator->run($request->scenario());
        $preset = $request->validated('preset');

        $simulation = StockSimulation::create([
            'name' => $request->validated('name') ?: StockSimulation::PRESETS[$preset].' · '.now()->format('d/m H:i'),
            'preset' => $preset,
            'scenario' => $results['scenario'],
            'horizon_days' => $results['horizon'],
            'start_date' => $results['start'],
            'baseline_wasted' => $results['baseline']['totals']['wasted'],
            'ai_wasted' => $results['ai']['totals']['wasted'],
            'baseline_unmet' => $results['baseline']['totals']['unmet'],
            'ai_unmet' => $results['ai']['totals']['unmet'],
            'ai_donated' => $results['ai']['totals']['donated'],
            'results' => $results,
            'user_id' => $request->user()?->getKey(),
        ]);

        return redirect()->route('simulations.show', $simulation)
            ->with('success', 'Simulation terminée : comparez les deux stratégies ci-dessous. Les stocks réels n’ont pas été modifiés.');
    }

    public function show(StockSimulation $simulation): View
    {
        return view('simulations.show', ['simulation' => $simulation->load('user:id,name')]);
    }

    public function destroy(StockSimulation $simulation): RedirectResponse
    {
        $simulation->delete();

        return redirect()->route('simulations.index')->with('success', 'Simulation supprimée.');
    }

    /**
     * Scénarios prêts à l'emploi, réglés sur les données réelles :
     * le magasin qui vend le plus, l'entrepôt qui détient le plus de lots datés.
     */
    private function presets(): array
    {
        $busiestStore = StockMovement::query()->sales()
            ->whereIn('source_site_id', Site::query()->active()->where('type', SiteType::Store->value)->select('id'))
            ->selectRaw('source_site_id, sum(quantity) as total')
            ->groupBy('source_site_id')->orderByDesc('total')->value('source_site_id');

        $biggestDated = Stock::query()->whereNotNull('batch_id')->where('quantity', '>', 0)
            ->whereIn('site_id', Site::query()->active()->logistics()->select('id'))
            ->selectRaw('site_id, sum(quantity) as total')
            ->groupBy('site_id')->orderByDesc('total')->value('site_id');

        return [
            'normal' => ['label' => StockSimulation::PRESETS['normal'], 'icon' => '📅',
                'text' => 'Aucun incident : on voit seulement ce que l’IA change au quotidien.', 'values' => []],
            'demand_peak' => ['label' => StockSimulation::PRESETS['demand_peak'], 'icon' => '🌙',
                'text' => 'Demande +35 % sur tous les produits, comme pendant le Ramadan pour les produits laitiers.',
                'values' => ['demand_change' => 35, 'demand_from' => 1, 'demand_to' => 14]],
            'truck_breakdown' => ['label' => StockSimulation::PRESETS['truck_breakdown'], 'icon' => '🚚',
                'text' => 'Les livraisons arrivent avec 2 jours de retard à partir du jour 2.',
                'values' => ['transport_delay' => 2, 'transport_from' => 2]],
            'store_closed' => ['label' => StockSimulation::PRESETS['store_closed'], 'icon' => '🏪',
                'text' => 'Le magasin qui vend le plus ferme 3 jours (travaux, panne électrique).',
                'values' => ['closed_site_id' => $busiestStore, 'closed_from' => 2, 'closed_days' => 3]],
            'cold_chain' => ['label' => StockSimulation::PRESETS['cold_chain'], 'icon' => '🌡️',
                'text' => 'Panne de froid dans l’entrepôt qui a le plus de lots : ils perdent 3 jours de DLC.',
                'values' => ['cold_site_id' => $biggestDated, 'cold_day' => 2, 'cold_lost_days' => 3]],
            'custom' => ['label' => StockSimulation::PRESETS['custom'], 'icon' => '🧪',
                'text' => 'Combinez librement les événements ci-dessous.', 'values' => []],
        ];
    }
}
