<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.18em] text-amber-warm">NutriTrace catalogue</p>
            <h1 class="mt-1 font-fraunces text-2xl font-semibold text-ink">Products</h1>
        </div>
    </x-slot>

    <div class="bg-cream py-10 sm:py-14">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="mb-8 max-w-2xl">
                <p class="text-sm leading-6 text-ink/60">Explore products and follow their journey through the NutriTrace network.</p>
            </div>

            @if($categories->isNotEmpty())
                <div class="mb-8 flex gap-2.5 overflow-x-auto pb-2 sidebar-scrollbar-hidden">
                    <a href="{{ route('front.products.index') }}"
                       class="shrink-0 rounded-full px-4 py-2 text-sm font-semibold transition-colors {{ blank($category) ? 'bg-forest text-white' : 'bg-white border border-ink/10 text-ink/70 hover:border-forest hover:text-forest' }}">
                        All categories
                    </a>
                    @foreach($categories as $categoryOption)
                        <a href="{{ route('front.products.index', ['category' => $categoryOption->slug]) }}"
                           class="shrink-0 rounded-full px-4 py-2 text-sm font-semibold transition-colors {{ (string) $category === (string) $categoryOption->slug || (string) $category === (string) $categoryOption->id ? 'bg-forest text-white' : 'bg-white border border-ink/10 text-ink/70 hover:border-forest hover:text-forest' }}">
                            {{ $categoryOption->name }}
                            <span class="ml-1 text-xs font-normal opacity-70">{{ $categoryOption->products_count }}</span>
                        </a>
                    @endforeach
                </div>
            @endif

            @if($products->isEmpty())
                <div class="rounded-2xl border border-dashed border-ink/15 bg-white px-6 py-16 text-center shadow-sm">
                    <h2 class="font-fraunces text-xl font-semibold text-ink">No products available yet</h2>
                    <p class="mt-2 text-sm text-ink/55">Products will appear here once they are added to the catalogue.</p>
                </div>
            @else
                <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach($products as $product)
                        <a href="{{ route('front.products.show', $product) }}" class="group overflow-hidden rounded-2xl border border-ink/8 bg-white shadow-sm transition duration-200 hover:-translate-y-1 hover:shadow-lg">
                            <div class="flex h-44 items-center justify-center bg-forest/5">
                                @if($product->image_url)
                                    <img src="{{ $product->image_url }}" alt="{{ $product->name }}" class="h-full w-full object-cover">
                                @else
                                    <span class="font-fraunces text-5xl font-semibold text-forest/25">{{ strtoupper(substr($product->name, 0, 1)) }}</span>
                                @endif
                            </div>
                            <div class="p-5">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <h2 class="font-fraunces text-xl font-semibold text-ink group-hover:text-forest">{{ $product->name }}</h2>
                                        <p class="mt-1 text-sm text-ink/50">{{ $product->categoryModel?->name ?? $product->category }}</p>
                                    </div>
                                    @if($product->verification_status === 'verified')
                                        <span class="rounded-full bg-emerald-500/10 px-2.5 py-1 text-xs font-semibold text-emerald-700">Verified</span>
                                    @endif
                                </div>
                                <p class="mt-4 line-clamp-2 text-sm leading-5 text-ink/60">{{ $product->description ?: 'Trace this product from origin to distribution.' }}</p>
                                <p class="mt-5 text-xs font-semibold text-forest">View product <span aria-hidden="true">→</span></p>
                            </div>
                        </a>
                    @endforeach
                </div>

                <div class="mt-8">{{ $products->links() }}</div>
            @endif
        </div>
    </div>
</x-app-layout>