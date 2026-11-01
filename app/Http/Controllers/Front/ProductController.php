<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $category = $request->query('category');

        $products = Product::with(['producer', 'categoryModel'])
            ->when($category, fn ($query) => $query->whereHas('categoryModel', function ($categoryQuery) use ($category) {
                $categoryQuery->where('slug', $category)->orWhere('id', $category);
            }))
            ->latest()
            ->paginate(12)
            ->withQueryString();

        $categories = Category::where('is_active', true)
            ->withCount('products')
            ->orderBy('name')
            ->get();

        return view('front.products.index', compact('products', 'categories', 'category'));
    }

    public function show(Product $product): View
    {
        $product->load(['producer', 'batches', 'certifications', 'categoryModel']);

        return view('front.products.show', compact('product'));
    }
}
