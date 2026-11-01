<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreProductRequest;
use App\Http\Requests\Admin\UpdateProductRequest;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Product::class);

        $query = $request->query('q');
        $search = is_string($query) ? trim($query) : '';
        $categoryId = filter_var($request->query('category_id'), FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1],
        ]) ?: null;
        $certificationFilter = $request->query('certification');
        $certification = in_array($certificationFilter, ['with', 'without'], true)
            ? $certificationFilter
            : 'all';
        $organic = in_array($request->query('organic'), ['organic', 'non_organic'], true)
            ? $request->query('organic')
            : 'all';
        $verificationStatus = in_array($request->query('verification_status'), ['pending', 'verified', 'rejected'], true)
            ? $request->query('verification_status')
            : 'all';

        $products = Product::query()
            ->with(['producer', 'categoryModel', 'certifications'])
            ->when(! $request->user()->can('manage_all_products'), fn ($query) => $query->where('producer_id', $request->user()->getKey()))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('barcode', 'like', "%{$search}%");
                });
            })
            ->when($categoryId, fn ($query) => $query->where('category_id', $categoryId))
            ->when($certification === 'with', fn ($query) => $query->whereHas('certifications'))
            ->when($certification === 'without', fn ($query) => $query->whereDoesntHave('certifications'))
            ->when($organic === 'organic', fn ($query) => $query->where('is_organic', true))
            ->when($organic === 'non_organic', fn ($query) => $query->where('is_organic', false))
            ->when($verificationStatus !== 'all', fn ($query) => $query->where('verification_status', $verificationStatus))
            ->latest()
            ->paginate(10)
            ->withQueryString();
        $categories = Category::orderBy('name')->get(['id', 'name']);

        return view('admin.products.index', compact('products', 'categories'));
    }

    public function create(Request $request)
    {
        $this->authorize('create', Product::class);

        $producers = $request->user()->can('manage_all_products')
            ? User::orderBy('name')->get(['id', 'name'])
            : User::whereKey($request->user()->getKey())->get(['id', 'name']);
        $categories = Category::where('is_active', true)->orderBy('name')->get();

        return view('admin.products.create', compact('producers', 'categories'));
    }

    public function store(StoreProductRequest $request)
    {
        $this->authorize('create', Product::class);

        $data = $request->validated();

        // Un producteur ne crée que pour son propre compte.
        if (! $request->user()->can('manage_all_products')) {
            $data['producer_id'] = $request->user()->getKey();
        }

        $data['category'] = Category::whereKey($data['category_id'])->value('name');

        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('products', 'public');

            if (! $imagePath) {
                return back()->withInput()->withErrors(['image' => 'Impossible d’enregistrer l’image.']);
            }

            $data['image'] = $imagePath;
        }

        Product::create($data);

        return redirect()->route('admin.products.index')
            ->with('success', 'Produit créé avec succès.');
    }

    public function show(Product $product)
    {
        $this->authorize('view', $product);

        $product->load('batches', 'producer', 'categoryModel');

        return view('admin.products.show', compact('product'));
    }

    public function edit(Request $request, Product $product)
    {
        $this->authorize('update', $product);

        $producers = $request->user()->can('manage_all_products')
            ? User::orderBy('name')->get(['id', 'name'])
            : User::whereKey($request->user()->getKey())->get(['id', 'name']);
        $categories = Category::query()
            ->where(function ($query) use ($product) {
                $query->where('is_active', true);

                if ($product->category_id) {
                    $query->orWhere('id', $product->category_id);
                }
            })
            ->orderBy('name')
            ->get();

        return view('admin.products.edit', compact('product', 'producers', 'categories'));
    }

    public function update(UpdateProductRequest $request, Product $product)
    {
        $this->authorize('update', $product);

        $data = $request->validated();

        // Un producteur ne peut pas réattribuer un produit à un autre compte.
        if (! $request->user()->can('manage_all_products')) {
            $data['producer_id'] = $request->user()->getKey();
        }

        $data['category'] = Category::whereKey($data['category_id'])->value('name');

        $oldImage = $product->image;
        $newImageUploaded = $request->hasFile('image');

        if ($newImageUploaded) {
            $imagePath = $request->file('image')->store('products', 'public');

            if (! $imagePath) {
                return back()->withInput()->withErrors(['image' => 'Impossible d’enregistrer l’image.']);
            }

            $data['image'] = $imagePath;
        } else {
            unset($data['image']);
        }

        $product->update($data);

        if ($newImageUploaded && $oldImage && $oldImage !== $data['image'] && ! Str::startsWith($oldImage, ['http://', 'https://'])) {
            Storage::disk('public')->delete($oldImage);
        }

        return redirect()->route('admin.products.index')
            ->with('success', 'Produit mis à jour avec succès.');
    }

    public function destroy(Product $product)
    {
        $this->authorize('delete', $product);

        $product->delete();

        return redirect()->route('admin.products.index')
            ->with('success', 'Produit supprimé avec succès.');
    }

}
