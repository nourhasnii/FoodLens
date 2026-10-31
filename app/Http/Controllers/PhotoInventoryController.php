<?php

namespace App\Http\Controllers;

use App\Http\Requests\PhotoInventoryApplyRequest;
use App\Http\Requests\PhotoInventoryRequest;
use App\Models\PhotoInventory;
use App\Models\Site;
use App\Services\Inventory\PhotoInventoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Écrans de l'inventaire par photo (IA de vision). */
class PhotoInventoryController extends Controller
{
    public function __construct(private readonly PhotoInventoryService $inventories) {}

    public function index(): View
    {
        return view('photo-inventories.index', [
            'sites' => Site::query()->active()->logistics()->orderBy('name')->get(['id', 'name', 'city']),
            'history' => PhotoInventory::query()->with(['site:id,name', 'user:id,name'])->latest()->paginate(10),
            'model' => config('stock.photo_inventory.model') ?: config('services.ollama.model', 'qwen2.5vl'),
        ]);
    }

    public function store(PhotoInventoryRequest $request): RedirectResponse
    {
        $path = $request->file('photo')->store('photo-inventories', PhotoInventoryService::DISK);

        $inventory = PhotoInventory::create([
            'site_id' => $request->validated('site_id'),
            'photo_path' => $path,
            'status' => PhotoInventory::PENDING,
            'user_id' => $request->user()?->getKey(),
        ]);

        $inventory = $this->inventories->analyze($inventory);

        return redirect()->route('photo-inventories.show', $inventory)->with(
            $inventory->status === PhotoInventory::ANALYZED ? 'success' : 'error',
            $inventory->status === PhotoInventory::ANALYZED
                ? 'Photo analysée : vérifiez le comptage proposé par l’IA avant de l’appliquer.'
                : 'L’IA n’a pas pu analyser la photo : saisissez le comptage à la main.'
        );
    }

    public function show(PhotoInventory $photoInventory): View
    {
        return view('photo-inventories.show', [
            'inventory' => $photoInventory->load(['site', 'user:id,name', 'applier:id,name']),
            'lowConfidence' => (float) config('stock.photo_inventory.low_confidence', 0.6),
        ]);
    }

    /** Relance l'analyse (ex. Ollama démarré après coup). */
    public function analyze(PhotoInventory $photoInventory): RedirectResponse
    {
        if ($photoInventory->isApplied()) {
            return back()->with('error', 'Cet inventaire a déjà été appliqué.');
        }

        $inventory = $this->inventories->analyze($photoInventory);

        return back()->with(
            $inventory->status === PhotoInventory::ANALYZED ? 'success' : 'error',
            $inventory->status === PhotoInventory::ANALYZED ? 'Nouvelle analyse terminée.' : $inventory->error
        );
    }

    public function apply(PhotoInventoryApplyRequest $request, PhotoInventory $photoInventory): RedirectResponse
    {
        $inventory = $this->inventories->apply($photoInventory, $request->validated('counts'), $request->user());

        return redirect()->route('photo-inventories.show', $inventory)->with('success', $inventory->adjustments_count > 0
            ? $inventory->adjustments_count.' ajustement(s) enregistré(s) dans les mouvements de stock.'
            : 'Inventaire validé : aucun écart avec le stock enregistré.');
    }

    /** La photo est privée (disque local) : elle est servie seulement aux utilisateurs autorisés. */
    public function photo(PhotoInventory $photoInventory): StreamedResponse
    {
        abort_unless(Storage::disk(PhotoInventoryService::DISK)->exists($photoInventory->photo_path), 404);

        return Storage::disk(PhotoInventoryService::DISK)->response($photoInventory->photo_path);
    }
}
