<nav x-data="{ open: false }" class="bg-white border-b border-ink/5">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <div class="shrink-0 flex items-center">
                    <a href="{{ url('/') }}" class="relative z-10 flex items-center gap-2 pointer-events-auto">
                        <span class="w-8 h-8 rounded-full bg-amber-warm flex items-center justify-center shadow-sm shadow-amber-warm/40 shrink-0">
                            <svg class="w-4 h-4 text-white" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M12 2L14.09 8.26L21 9.27L16 14.14L17.18 21.02L12 17.77L6.82 21.02L8 14.14L3 9.27L9.91 8.26L12 2Z" fill="currentColor"/>
                            </svg>
                        </span>
                        <span class="font-fraunces font-extrabold text-lg text-ink tracking-tight hidden sm:block">NutriTrace</span>
                    </a>
                </div>

                <div class="hidden space-x-1 sm:-my-px sm:ms-8 sm:flex">
                    <x-nav-link :href="route('front.products.index')" :active="request()->routeIs('front.products.*')">
                        {{ __('Catalogue') }}
                    </x-nav-link>
                    @auth
                        <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                            {{ __('Tableau de bord') }}
                        </x-nav-link>
                        @if(Auth::user()->can('manage_products'))
                            <x-nav-link :href="route('admin.products.index')" :active="request()->routeIs('admin.products.*')">
                                {{ __('Produits') }}
                            </x-nav-link>
                            <x-nav-link :href="route('admin.batches.index')" :active="request()->routeIs('admin.batches.*')">
                                {{ __('Lots') }}
                            </x-nav-link>
                            <x-nav-link :href="route('admin.certifications.index')" :active="request()->routeIs('admin.certifications.*')">
                                {{ __('Certifications') }}
                            </x-nav-link>
                            <x-nav-link :href="route('admin.events.index')" :active="request()->routeIs('admin.events.*')">
                                {{ __('Événements') }}
                            </x-nav-link>
                        @endif
                    @endauth
                </div>
            </div>

            <div class="hidden sm:flex sm:items-center sm:ms-6">
                @auth
                    <div class="me-2">
                        <x-notification-bell />
                    </div>

                    <x-dropdown align="right" width="48">
                        <x-slot name="trigger">
                            <button class="inline-flex items-center px-3 py-2 border border-ink/10 text-sm leading-4 font-medium rounded-full text-ink/70 bg-cream/50 hover:text-ink hover:bg-cream focus:outline-none transition ease-in-out duration-150">
                                <x-user-avatar :user="Auth::user()" size="sm" class="me-2 bg-forest/10 text-forest" />
                                <div class="me-1">{{ Auth::user()->name }}</div>
                                <svg class="fill-current h-4 w-4 text-ink/40" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </button>
                        </x-slot>

                        <x-slot name="content">
                            <x-dropdown-link :href="route('profile.edit')">
                                {{ __('Profil') }}
                            </x-dropdown-link>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <x-dropdown-link :href="route('logout')"
                                        onclick="event.preventDefault();
                                                    this.closest('form').submit();">
                                    {{ __('Déconnexion') }}
                                </x-dropdown-link>
                            </form>
                        </x-slot>
                    </x-dropdown>
                @else
                    <div class="flex items-center gap-2">
                        <a href="{{ route('front.products.index') }}" class="inline-flex items-center rounded-full px-4 py-2 text-sm font-medium text-ink/70 hover:text-ink hover:bg-cream transition-colors">
                            {{ __('Catalogue') }}
                        </a>
                        <a href="{{ route('login') }}" class="inline-flex items-center rounded-full px-4 py-2 text-sm font-medium text-ink/70 hover:text-ink hover:bg-cream transition-colors">
                            {{ __('Connexion') }}
                        </a>
                        <a href="{{ route('register') }}" class="inline-flex items-center rounded-full bg-amber-warm px-4 py-2 text-sm font-semibold text-white hover:bg-amber-warm/90 transition-colors">
                            {{ __('Inscription') }}
                        </a>
                    </div>
                @endauth
            </div>

            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-full text-ink/50 hover:text-ink hover:bg-cream focus:outline-none transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="route('front.products.index')" :active="request()->routeIs('front.products.*')">
                {{ __('Catalogue') }}
            </x-responsive-nav-link>
            @auth
                <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                    {{ __('Tableau de bord') }}
                </x-responsive-nav-link>
                @if(Auth::user()->can('manage_products'))
                    <x-responsive-nav-link :href="route('admin.products.index')" :active="request()->routeIs('admin.products.*')">
                        {{ __('Produits') }}
                    </x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('admin.batches.index')" :active="request()->routeIs('admin.batches.*')">
                        {{ __('Lots') }}
                    </x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('admin.certifications.index')" :active="request()->routeIs('admin.certifications.*')">
                        {{ __('Certifications') }}
                    </x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('admin.events.index')" :active="request()->routeIs('admin.events.*')">
                        {{ __('Événements') }}
                    </x-responsive-nav-link>
                @endif
            @endauth
        </div>

        @auth
            <div class="pt-4 pb-1 border-t border-ink/5">
                <div class="px-4 flex items-center gap-3">
                    <x-user-avatar :user="Auth::user()" size="lg" class="bg-forest/10 text-forest/60" />
                    <div class="min-w-0">
                        <div class="font-medium text-base text-ink truncate">{{ Auth::user()->name }}</div>
                        <div class="font-medium text-sm text-ink/50 truncate">{{ Auth::user()->email }}</div>
                    </div>
                </div>

                <div class="mt-3 space-y-1">
                    <x-responsive-nav-link :href="route('notifications.index')" :active="request()->routeIs('notifications.*')">
                        {{ __('Notifications') }}
                    </x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('profile.edit')">
                        {{ __('Profil') }}
                    </x-responsive-nav-link>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <x-responsive-nav-link :href="route('logout')"
                                onclick="event.preventDefault();
                                            this.closest('form').submit();">
                            {{ __('Déconnexion') }}
                        </x-responsive-nav-link>
                    </form>
                </div>
            </div>
        @else
            <div class="pt-4 pb-3 border-t border-ink/5 space-y-1">
                <x-responsive-nav-link :href="route('login')">
                    {{ __('Connexion') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('register')">
                    {{ __('Inscription') }}
                </x-responsive-nav-link>
            </div>
        @endauth
    </div>
</nav>
