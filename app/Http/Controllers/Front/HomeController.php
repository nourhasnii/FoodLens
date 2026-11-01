<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Batch;
use App\Models\Category;
use App\Models\Product;
use Illuminate\View\View;

class HomeController extends Controller
{
    /**
     * Public landing page with live catalogue data.
     */
    public function index(): View
    {
        $categories = Category::query()
            ->where('is_active', true)
            ->withCount(['products' => fn ($query) => $query->where('verification_status', 'verified')])
            ->orderBy('name')
            ->get();

        $products = Product::query()
            ->with('categoryModel')
            ->where('verification_status', 'verified')
            ->latest()
            ->take(12)
            ->get();

        $batches = Batch::query()
            ->with('product')
            ->where('status', 'active')
            ->latest()
            ->take(6)
            ->get();

        $featuredTimeline = Batch::query()
            ->with(['product', 'events' => fn ($query) => $query->orderBy('event_date')])
            ->has('events')
            ->latest()
            ->first();

        return view('welcome', compact('categories', 'products', 'batches', 'featuredTimeline'));
    }
}
