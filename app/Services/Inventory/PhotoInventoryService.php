<?php

namespace App\Services\Inventory;

use App\Enums\StockMovementType;
use App\Models\PhotoInventory;
use App\Models\Product;
use App\Models\Stock;
use App\Models\User;
use App\Services\OllamaService;
use App\Services\Stock\StockService;
use App\Support\BatchAttributes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Inventaire par photo.
 *
 *  1. L'IA de vision (Ollama, modèle qwen2.5vl de l'équipe) reçoit la photo et la liste des produits
 *     stockés sur le site, et renvoie un comptage JSON par produit avec un indice de confiance.
 *  2. Les noms lus par l'IA sont rapprochés des produits du site (similarité de texte).
 *  3. Un humain vérifie et corrige le comptage : l'IA propose, l'utilisateur décide.
 *  4. Chaque écart validé devient un mouvement d'AJUSTEMENT (traçable dans l'historique des stocks).
 *
 * Si Ollama n'est pas lancé ou répond mal, l'inventaire passe en saisie manuelle : rien ne plante.
 */
class PhotoInventoryService
{
    public const DISK = 'local';

    public function __construct(
        private readonly OllamaService $ollama,
        private readonly StockService $stocks,
    ) {}

    /** Analyse la photo et remplit le comptage proposé (ou bascule en saisie manuelle). */
    public function analyze(PhotoInventory $inventory): PhotoInventory
    {
        $products = $this->productsAt($inventory->site_id);
        $items = $this->baseItems($products, $inventory->site_id);
        $started = microtime(true);

        try {
            $raw = Storage::disk(self::DISK)->get($inventory->photo_path);
            if ($raw === null) {
                throw new \RuntimeException('Photo introuvable sur le disque.');
            }

            // Un modèle de vision sur processeur (sans carte graphique) peut dépasser
            // la limite de PHP (30 à 120 s selon php.ini) : on attend autant que le délai d'Ollama.
            if (function_exists('set_time_limit')) {
                @set_time_limit((int) config('services.ollama.timeout', 300) + 60);
            }

            $model = config('stock.photo_inventory.model') ?: null;
            $answer = $this->ollama->generateJson(
                prompt: $this->prompt($products),
                systemPrompt: 'Tu es un assistant d’inventaire pour un entrepôt alimentaire. Tu comptes précisément des unités visibles et tu réponds uniquement en JSON.',
                model: $model,
                images: [base64_encode($this->shrink($raw))],
            );

            $decoded = $this->decode($answer['response']);
            [$items, $unmatched] = $this->match($decoded['items'], $items, $products);

            $inventory->update([
                'status' => PhotoInventory::ANALYZED,
                'ai_model' => $answer['model'],
                'items' => $items,
                'unmatched' => $unmatched,
                'ai_notes' => $decoded['notes'],
                'error' => null,
                'duration_ms' => (int) round((microtime(true) - $started) * 1000),
            ]);
        } catch (Throwable $e) {
            Log::warning('[PhotoInventory] Analyse impossible', ['id' => $inventory->getKey(), 'error' => $e->getMessage()]);

            $inventory->update([
                'status' => PhotoInventory::FAILED,
                'items' => $items,
                'unmatched' => [],
                'error' => $this->friendlyError($e),
                'duration_ms' => (int) round((microtime(true) - $started) * 1000),
            ]);
        }

        return $inventory->refresh();
    }

    /**
     * Applique le comptage validé : un mouvement d'ajustement par ligne de stock qui change.
     *
     * @param  array<int, float|int|string|null>  $counts  quantité comptée par produit (vide = produit non compté)
     */
    public function apply(PhotoInventory $inventory, array $counts, ?User $user = null): PhotoInventory
    {
        if ($inventory->isApplied()) {
            throw ValidationException::withMessages(['counts' => 'Cet inventaire a déjà été appliqué.']);
        }

        return DB::transaction(function () use ($inventory, $counts, $user) {
            $items = $inventory->items ?? [];
            $adjustments = 0;

            foreach ($items as &$item) {
                $value = $counts[$item['product_id']] ?? null;
                if ($value === null || $value === '') {
                    $item['final_qty'] = null; // non compté : on ne touche à rien

                    continue;
                }
                $final = round((float) $value, 2);
                $item['final_qty'] = $final;
                $adjustments += $this->adjustProduct($inventory, (int) $item['product_id'], $final, $user);
            }
            unset($item);

            $inventory->update([
                'items' => $items,
                'status' => PhotoInventory::APPLIED,
                'adjustments_count' => $adjustments,
                'applied_by' => $user?->getKey(),
                'applied_at' => now(),
            ]);

            return $inventory->refresh();
        });
    }

    /* ------------------------------------------------------------------ */

    /** Produits présents sur le site (au moins une ligne de stock). */
    public function productsAt(int $siteId)
    {
        return Product::query()
            ->whereIn('id', Stock::query()->where('site_id', $siteId)->select('product_id'))
            ->orderBy('name')
            ->get(['id', 'name', 'unit']);
    }

    private function baseItems($products, int $siteId): array
    {
        return $products->map(fn (Product $p) => [
            'product_id' => $p->getKey(),
            'name' => $p->name,
            'unit' => $p->unit,
            // Quantité physique enregistrée (lots bloqués compris : ils sont sur la photo)
            'system_qty' => round((float) Stock::query()->where('site_id', $siteId)->where('product_id', $p->getKey())->sum('quantity'), 2),
            'ai_count' => null,
            'confidence' => null,
            'ai_label' => null,
            'final_qty' => null,
        ])->values()->all();
    }

    private function prompt($products): string
    {
        $list = $products->values()->map(fn (Product $p, int $i) => ($i + 1).'. '.$p->name.' (unité : '.($p->unit ?: 'unité').')')->join("\n");

        return <<<PROMPT
        Compte les unités visibles de chaque produit sur cette photo de rayon ou de palette.
        Produits attendus sur ce site :
        {$list}

        Règles :
        - Compte des unités individuelles (pots, bouteilles, briques, sachets), pas des cartons, sauf si l'unité du produit est un carton.
        - Utilise exactement le nom de la liste quand tu reconnais un produit ; sinon décris l'objet en quelques mots.
        - N'invente rien : si un produit n'est pas visible, ne le mets pas.
        - confidence : 0 à 1 (1 = certain).

        Réponds uniquement avec ce JSON :
        {"items": [{"product": "nom", "count": 12, "confidence": 0.8}], "notes": "remarque courte en français (rangement, étiquettes illisibles, produits abîmés…)"}
        PROMPT;
    }

    /** @return array{items: list<array{product:string,count:float,confidence:?float}>, notes: ?string} */
    private function decode(string $response): array
    {
        $json = json_decode($response, true);
        if (! is_array($json)) {
            // Certains modèles entourent le JSON de texte : on récupère le premier objet {…}
            if (preg_match('/\{.*\}/s', $response, $m)) {
                $json = json_decode($m[0], true);
            }
        }
        if (! is_array($json)) {
            throw new \UnexpectedValueException('Réponse de l’IA illisible (JSON attendu).');
        }

        $rows = $json['items'] ?? $json['products'] ?? [];
        $items = [];
        foreach (is_array($rows) ? $rows : [] as $row) {
            if (! is_array($row)) {
                continue;
            }
            $name = trim((string) ($row['product'] ?? $row['name'] ?? ''));
            $count = $row['count'] ?? $row['quantity'] ?? null;
            if ($name === '' || ! is_numeric($count) || $count < 0) {
                continue;
            }
            $confidence = isset($row['confidence']) && is_numeric($row['confidence'])
                ? max(0.0, min(1.0, (float) $row['confidence'])) : null;
            $items[] = ['product' => Str::limit($name, 120, ''), 'count' => round((float) $count, 2), 'confidence' => $confidence];
        }

        return ['items' => $items, 'notes' => isset($json['notes']) ? Str::limit(trim((string) $json['notes']), 500) : null];
    }

    /** Rapproche les noms lus par l'IA des produits du site. */
    private function match(array $detections, array $items, $products): array
    {
        $threshold = (float) config('stock.photo_inventory.match_threshold', 55);
        $byId = collect($items)->keyBy('product_id')->all();
        $unmatched = [];

        foreach ($detections as $det) {
            $best = null;
            $bestScore = 0.0;
            foreach ($products as $p) {
                $score = $this->similarity($det['product'], $p->name);
                if ($score > $bestScore) {
                    [$best, $bestScore] = [$p->getKey(), $score];
                }
            }

            if ($best !== null && $bestScore >= $threshold) {
                // Le même produit vu plusieurs fois (deux étagères) : on additionne
                $byId[$best]['ai_count'] = round(($byId[$best]['ai_count'] ?? 0) + $det['count'], 2);
                $byId[$best]['confidence'] = $byId[$best]['confidence'] === null
                    ? $det['confidence'] : min($byId[$best]['confidence'], $det['confidence'] ?? 1.0);
                $byId[$best]['ai_label'] = $det['product'];
            } else {
                $unmatched[] = $det;
            }
        }

        return [array_values($byId), $unmatched];
    }

    /** Similarité (0-100) entre deux noms, sans accents ni majuscules ; 100 si l'un contient l'autre. */
    public function similarity(string $a, string $b): float
    {
        $na = $this->normalize($a);
        $nb = $this->normalize($b);
        if ($na === '' || $nb === '') {
            return 0.0;
        }
        if (str_contains($na, $nb) || str_contains($nb, $na)) {
            return 100.0;
        }
        // Mots en commun (ex. « yaourt nature » ↔ « Yaourt nature 125 g »)
        // Les mots d'emballage (« pot de », « bouteille de ») ne disent rien du produit
        $packaging = ['pot', 'pots', 'bouteille', 'bouteilles', 'brique', 'briques', 'boite', 'boites', 'sachet', 'sachets',
            'paquet', 'paquets', 'carton', 'cartons', 'pack', 'packs', 'unite', 'unites', 'des', 'les', 'aux'];
        $keep = fn ($w) => mb_strlen($w) > 2 && ! in_array($w, $packaging, true);
        $wa = array_filter(explode(' ', $na), $keep);
        $wb = array_filter(explode(' ', $nb), $keep);
        $common = count(array_intersect($wa, $wb));
        $words = $common > 0 ? 100 * $common / max(1, min(count($wa), count($wb))) : 0;

        similar_text($na, $nb, $chars);

        return round(max($words * 0.9, $chars), 1);
    }

    private function normalize(string $s): string
    {
        $s = Str::lower(Str::ascii($s));

        return trim(preg_replace('/[^a-z0-9]+/', ' ', $s));
    }

    /**
     * Ajuste les lignes d'un produit pour atteindre la quantité comptée.
     * Manque : retiré d'abord du stock sans lot, puis des lots les plus proches de leur DLC.
     * Surplus : ajouté au stock sans lot (ou au lot le plus récent s'il n'y en a pas).
     */
    private function adjustProduct(PhotoInventory $inventory, int $productId, float $final, ?User $user): int
    {
        $lines = Stock::query()->with('batch')
            ->where('site_id', $inventory->site_id)->where('product_id', $productId)
            ->get();
        $current = round((float) $lines->sum('quantity'), 2);
        $delta = round($final - $current, 2);
        if (abs($delta) < 0.01) {
            return 0;
        }

        $reason = 'Inventaire photo #'.$inventory->getKey();
        $note = $inventory->status === PhotoInventory::FAILED ? 'Comptage manuel (IA indisponible)' : 'Comptage IA vérifié par l’utilisateur';
        $count = 0;

        if ($delta > 0) {
            $target = $lines->firstWhere('batch_id', null)
                ?? $lines->sortByDesc(fn (Stock $l) => BatchAttributes::expiryDate($l->batch)?->timestamp ?? PHP_INT_MAX)->first();
            $this->stocks->record([
                'type' => StockMovementType::Adjustment, 'site_id' => $inventory->site_id, 'product_id' => $productId,
                'batch_id' => $target?->batch_id, 'counted_quantity' => (float) ($target?->quantity ?? 0) + $delta,
                'reason' => $reason, 'notes' => $note,
            ], $user);

            return 1;
        }

        $missing = -$delta;
        $ordered = $lines->sortBy(fn (Stock $l) => [$l->batch_id === null ? 0 : 1, BatchAttributes::expiryDate($l->batch)?->timestamp ?? 0]);
        foreach ($ordered as $line) {
            if ($missing <= 0.0049) {
                break;
            }
            $take = min((float) $line->quantity, $missing);
            if ($take <= 0) {
                continue;
            }
            $this->stocks->record([
                'type' => StockMovementType::Adjustment, 'site_id' => $inventory->site_id, 'product_id' => $productId,
                'batch_id' => $line->batch_id, 'counted_quantity' => round((float) $line->quantity - $take, 2),
                'reason' => $reason, 'notes' => $note,
            ], $user);
            $missing = round($missing - $take, 2);
            $count++;
        }

        return $count;
    }

    /**
     * Réduit la photo (côté le plus long : max_side px, JPEG) : l'analyse est bien plus rapide
     * et le comptage reste lisible. Sans l'extension GD, la photo est envoyée telle quelle.
     */
    private function shrink(string $raw): string
    {
        $max = (int) config('stock.photo_inventory.max_side', 1280);
        if ($max <= 0 || ! function_exists('imagecreatefromstring')) {
            return $raw;
        }

        $image = @imagecreatefromstring($raw);
        if ($image === false) {
            return $raw;
        }

        [$w, $h] = [imagesx($image), imagesy($image)];
        if (max($w, $h) <= $max) {
            imagedestroy($image);

            return $raw;
        }

        $ratio = $max / max($w, $h);
        $resized = imagescale($image, (int) round($w * $ratio), (int) round($h * $ratio));
        ob_start();
        imagejpeg($resized ?: $image, null, 85);
        $out = (string) ob_get_clean();
        imagedestroy($image);
        if ($resized) {
            imagedestroy($resized);
        }

        return $out !== '' ? $out : $raw;
    }

    private function friendlyError(Throwable $e): string
    {
        return match (true) {
            $e instanceof \Illuminate\Http\Client\ConnectionException => 'L’IA de vision (Ollama) n’est pas joignable : lancez « ollama serve » puis réessayez, ou saisissez le comptage à la main.',
            $e instanceof \Illuminate\Http\Client\RequestException => 'Ollama a refusé la demande (modèle de vision installé ? « ollama pull qwen2.5vl »). Vous pouvez saisir le comptage à la main.',
            $e instanceof \UnexpectedValueException => 'L’IA a répondu dans un format inattendu. Réessayez avec une photo plus nette, ou saisissez le comptage à la main.',
            default => 'Analyse impossible ('.Str::limit($e->getMessage(), 140).'). Saisissez le comptage à la main.',
        };
    }
}
