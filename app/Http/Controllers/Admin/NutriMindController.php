<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Batch;
use App\Models\OcrCheck;
use App\Models\Product;
use App\Models\QualityCheck;
use App\Models\RiskAssessment;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\NutriMind\ImpactAnalysisService;
use App\Services\NutriMind\NutriMindService;
use App\Services\NutriMind\RecommendationService;
use App\Services\NutriMind\ScenarioSimulator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * NutriMind — console explicable de décision (Phase 4).
 *
 * Présente le score de risque justifié, les facteurs contributifs, le graphe
 * d'impact, les recommandations correctives sûres, la simulation « what-if » et
 * l'historique comparé des évaluations. Toutes les actions sont soumises à la
 * permission `manage_products` (contrôle serveur) : le moteur recommande,
 * l'utilisateur décide.
 */
class NutriMindController extends Controller
{
    public function __construct(
        private readonly NutriMindService $engine,
        private readonly ImpactAnalysisService $impact,
        private readonly ScenarioSimulator $simulator,
        private readonly RecommendationService $recommendations,
        private readonly AuditLogService $audit,
    ) {}

    /**
     * Vue d'ensemble : entités les plus à risque et dernières évaluations.
     */
    public function index(): View
    {
        $this->authorize('manage_products', Product::class);

        $user = auth()->user();

        // Un producteur n'évalue que ses propres produits ; le distributeur
        // (permission `manage_all_products`) voit l'ensemble du périmètre.
        $products = Product::query()
            ->when(! $user->can('manage_all_products'), fn ($query) => $query->where('producer_id', $user->getKey()))
            ->with(['certifications', 'batches'])
            ->get();
        $productIds = $products->pluck('id');

        // Préchargement agrégé : évite les requêtes N+1 pendant l'évaluation.
        $qualityByProduct = QualityCheck::query()
            ->whereIn('product_id', $productIds)
            ->where('status', 'completed')
            ->whereNotNull('quality_score')
            ->orderBy('quality_score')
            ->get()->groupBy('product_id')->map->first();

        $ocrByProduct = OcrCheck::query()
            ->whereIn('product_id', $productIds)
            ->where('status', 'completed')
            ->whereNotNull('inconsistency_score')
            ->orderByDesc('inconsistency_score')
            ->get()->groupBy('product_id')->map->first();

        $evaluations = $products->map(function (Product $product) use ($qualityByProduct, $ocrByProduct) {
            $result = $this->engine->evaluate($product, [], [
                'quality' => $qualityByProduct->get($product->getKey()),
                'ocr' => $ocrByProduct->get($product->getKey()),
            ]);

            return [
                'type' => 'product',
                'id' => $product->getKey(),
                'label' => $product->name,
                'score' => $result['score'],
                'severity' => $result['severity'],
                'critical_override' => $result['critical_override'],
                'completeness' => $result['data_completeness'],
                'top' => collect($result['risk_findings'])->first(),
            ];
        })->sortByDesc('score')->values();

        $summary = [
            'evaluated' => $evaluations->count(),
            'critical' => $evaluations->where('severity', 'critical')->count(),
            'warning' => $evaluations->where('severity', 'warning')->count(),
            'info' => $evaluations->where('severity', 'info')->count(),
            'average' => round((float) $evaluations->avg('score'), 2),
        ];

        $topRisks = $evaluations->take(10);

        $recentAssessments = RiskAssessment::query()
            ->real()
            ->with('creator')
            ->latest('created_at')
            ->latest('id')
            ->limit(10)
            ->get();

        return view('admin.nutrimind.index', compact('summary', 'topRisks', 'recentAssessments'));
    }

    /**
     * Détail d'une entité : évaluation justifiée, impact, recommandations,
     * simulation et historique comparé.
     */
    public function show(Request $request, string $type, int $id): View
    {
        $entity = $this->resolveEntity($type, $id);
        $this->authorizeEntity($entity);

        $evaluation = $this->engine->evaluate($entity);
        $impact = $this->impact->analyze($entity);
        $recommendations = $this->recommendations->recommend($entity, $evaluation);

        $history = $this->engine->history($entity, 15);
        $comparison = $history->count() >= 2
            ? $this->engine->compare($history[1], $history[0])
            : null;

        $scenarios = collect(config('nutrimind.scenarios', []))
            ->map(fn (array $scenario, string $key) => ['key' => $key] + $scenario)
            ->values()
            ->all();

        $selected = array_values(array_intersect(
            (array) $request->query('scenarios', []),
            array_column($scenarios, 'key')
        ));

        $simulation = $selected === [] ? null : $this->simulator->simulate($entity, $selected);

        return view('admin.nutrimind.show', compact(
            'entity', 'type', 'evaluation', 'impact', 'recommendations',
            'history', 'comparison', 'scenarios', 'selected', 'simulation'
        ));
    }

    /**
     * Enregistre une évaluation réelle dans l'historique (trace la décision).
     */
    public function assess(string $type, int $id): RedirectResponse
    {
        $entity = $this->resolveEntity($type, $id);
        $this->authorizeEntity($entity);

        $evaluation = $this->engine->evaluate($entity);
        $top = collect($this->recommendations->recommend($entity, $evaluation))->first();

        $assessment = $this->engine->assess($entity, auth()->user(), $top['title'] ?? null);

        $this->audit->record('nutrimind.assessed', $entity, [], [
            'assessment_id' => $assessment->getKey(),
            'score' => (float) $assessment->score,
            'severity' => $assessment->severity,
            'recommendation' => $assessment->recommendation,
        ]);

        return back()->with('success', sprintf(
            'Évaluation NutriMind enregistrée : score %s/100 (%s).',
            rtrim(rtrim((string) $assessment->score, '0'), '.'),
            $assessment->severity
        ));
    }

    /**
     * Journalise une action corrective engagée dans le journal d'audit.
     */
    public function recordAction(Request $request, string $type, int $id): RedirectResponse
    {
        $entity = $this->resolveEntity($type, $id);
        $this->authorizeEntity($entity);

        $data = $request->validate([
            'rule' => ['required', 'string', 'max:100'],
            'label' => ['required', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $this->audit->record('nutrimind.action_recorded', $entity, [], [
            'rule' => $data['rule'],
            'label' => $data['label'],
            'note' => $data['note'] ?? null,
        ]);

        return back()->with('success', 'Action corrective journalisée dans le journal d’audit.');
    }

    /**
     * Contrôle d'accès à l'entité évaluée (contrôle serveur).
     *
     * Un producteur ne peut évaluer que ses propres produits et lots. Les
     * fournisseurs (User) sont réservés aux profils à périmètre élargi.
     */
    private function authorizeEntity(Model $entity): void
    {
        if ($entity instanceof Product || $entity instanceof Batch) {
            $this->authorize('view', $entity);

            return;
        }

        abort_unless(auth()->user()->can('manage_all_products'), 403);
    }

    /**
     * Résout l'entité évaluable depuis le type d'URL (product|batch|supplier).
     */
    private function resolveEntity(string $type, int $id): Model
    {
        return match ($type) {
            'product' => Product::query()->findOrFail($id),
            'batch' => Batch::query()->findOrFail($id),
            'supplier' => User::query()->findOrFail($id),
            default => abort(404),
        };
    }
}
