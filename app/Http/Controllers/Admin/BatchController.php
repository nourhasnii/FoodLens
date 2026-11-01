<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Batch;
use App\Models\Product;
use App\Services\BatchQrCodeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class BatchController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Batch::class);

        $batches = Batch::with('product')
            ->when(! $request->user()->can('manage_all_products'), fn ($query) => $query->whereHas('product', fn ($product) => $product->where('producer_id', $request->user()->getKey())))
            ->when($request->filled('search'), fn ($query) => $query->where('lot_number', 'like', '%' . $request->string('search') . '%'))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->when($request->filled('product_id'), fn ($query) => $query->where('product_id', $request->integer('product_id')))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $products = $this->availableProducts($request)->get(['id', 'name']);

        return view('admin.batches.index', compact('batches', 'products'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $this->authorize('create', Batch::class);

        $products = $this->availableProducts($request)->get(['id', 'name', 'category', 'unit']);

        return view('admin.batches.create', compact('products'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->authorize('create', Batch::class);

        $data = $this->validatedData($request);

        // Un producteur ne peut créer un lot que pour ses propres produits.
        $this->authorize('view', Product::findOrFail($data['product_id']));
        $this->guardRecall($request, $data);

        Batch::create($data);

        return redirect()->route('admin.batches.index')->with('success', 'Lot créé avec succès.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Batch $batch)
    {
        $this->authorize('view', $batch);

        $batch->load('product');
        $qrCodeExists = $batch->qr_code_path && Storage::disk('public')->exists($batch->qr_code_path);

        return view('admin.batches.show', compact('batch', 'qrCodeExists'));
    }

    public function downloadQr(Batch $batch)
    {
        $this->authorize('view', $batch);

        $disk = Storage::disk('public');

        if (! $batch->qr_code_path || ! $disk->exists($batch->qr_code_path)) {
            return redirect()->route('admin.batches.show', $batch)->with('error', 'QR code is not available.');
        }

        $extension = pathinfo($batch->qr_code_path, PATHINFO_EXTENSION);

        return response()->download($disk->path($batch->qr_code_path), 'batch-' . $batch->lot_number . '-qr.' . $extension);
    }

    public function regenerateQr(Batch $batch, BatchQrCodeService $qrCodeService)
    {
        $this->authorize('update', $batch);

        $qrCodeService->generate($batch);

        return redirect()->route('admin.batches.show', $batch)->with('success', 'QR code regenerated.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, Batch $batch)
    {
        $this->authorize('update', $batch);

        $products = $this->availableProducts($request)->get(['id', 'name', 'category', 'unit']);

        return view('admin.batches.edit', compact('batch', 'products'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Batch $batch)
    {
        $this->authorize('update', $batch);

        $data = $this->validatedData($request, $batch);

        $this->authorize('view', Product::findOrFail($data['product_id']));
        $this->guardRecall($request, $data);

        $batch->update($data);

        return redirect()->route('admin.batches.index')->with('success', 'Lot mis à jour avec succès.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Batch $batch)
    {
        $this->authorize('delete', $batch);

        $batch->delete();

        return redirect()->route('admin.batches.index')->with('success', 'Lot supprimé avec succès.');
    }

    /**
     * Produits visibles par l'utilisateur (tous, ou seulement les siens).
     */
    private function availableProducts(Request $request)
    {
        return Product::query()
            ->when(! $request->user()->can('manage_all_products'), fn ($query) => $query->where('producer_id', $request->user()->getKey()))
            ->orderBy('name');
    }

    /**
     * Le passage d'un lot au statut « recalled » (rappel) est réservé aux
     * profils disposant de la permission `launch_recall`.
     */
    private function guardRecall(Request $request, array $data): void
    {
        if (($data['status'] ?? null) === 'recalled') {
            abort_unless($request->user()->can('launch_recall'), 403);
        }
    }

    private function validatedData(Request $request, ?Batch $batch = null): array
    {
        return $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'lot_number' => ['required', 'string', 'min:3', 'max:50', Rule::unique('batches', 'lot_number')->ignore($batch?->id)],
            'production_date' => ['required', 'date', 'before_or_equal:today'],
            'expiration_date' => ['required', 'date', 'after:production_date'],
            'quantity' => ['required', 'numeric', 'min:0.01', 'max:999999'],
            'unit' => ['required', 'string', 'min:1', 'max:20'],
            'carbon_footprint' => ['nullable', 'numeric', 'min:0', 'max:99999'],
            'status' => ['required', Rule::in(['active', 'expired', 'recalled'])],
        ], [
            'product_id.required' => 'Veuillez sélectionner un produit.',
            'product_id.exists' => 'Le produit sélectionné n’existe pas.',
            'lot_number.required' => 'Le numéro de lot est obligatoire.',
            'lot_number.string' => 'Le numéro de lot doit être un texte.',
            'lot_number.min' => 'Le numéro de lot doit contenir au moins 3 caractères.',
            'lot_number.max' => 'Le numéro de lot ne doit pas dépasser 50 caractères.',
            'lot_number.unique' => 'Ce numéro de lot existe déjà.',
            'production_date.required' => 'La date de production est obligatoire.',
            'production_date.date' => 'La date de production doit être une date valide.',
            'production_date.before_or_equal' => 'La date de production ne peut pas être dans le futur.',
            'expiration_date.required' => "La date d'expiration est obligatoire.",
            'expiration_date.date' => 'La date d’expiration doit être une date valide.',
            'expiration_date.after' => "La date d'expiration doit être postérieure à la date de production.",
            'quantity.required' => 'La quantité est obligatoire.',
            'quantity.numeric' => 'La quantité doit être un nombre.',
            'quantity.min' => 'La quantité doit être supérieure à 0.',
            'quantity.max' => 'La quantité ne doit pas dépasser 999999.',
            'unit.required' => 'L’unité est obligatoire.',
            'unit.string' => 'L’unité doit être un texte.',
            'unit.min' => 'L’unité doit contenir au moins 1 caractère.',
            'unit.max' => 'L’unité ne doit pas dépasser 20 caractères.',
            'carbon_footprint.numeric' => 'L’empreinte carbone doit être un nombre.',
            'carbon_footprint.min' => 'L’empreinte carbone ne peut pas être négative.',
            'carbon_footprint.max' => 'L’empreinte carbone ne doit pas dépasser 99999.',
            'status.required' => 'Le statut est obligatoire.',
            'status.in' => 'Le statut sélectionné n’est pas valide.',
        ]);
    }
}
