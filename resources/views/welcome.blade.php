<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>NutriTrace — Traçabilité alimentaire vérifiable</title>

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,400;0,9..144,500;0,9..144,600;0,9..144,700;0,9..144,800;0,9..144,900;1,9..144,400&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-cream text-ink font-inter antialiased">

        {{-- ==============================================================
             HERO COMPLÈTE (Navbar + Contenu + Vague blanche)
             ============================================================== --}}
        <section class="relative bg-forest overflow-hidden min-h-[620px] lg:min-h-[680px]">

            {{-- =========================================================
                 4. PETITES ILLUSTRATIONS DÉCORATIVES — LINE-ART
                 (traits fins, pas remplis, semi-transparents)
                 ========================================================= --}}

            {{-- Coin haut-gauche — fleur line-art --}}
            <svg class="absolute top-10 left-6 lg:top-14 lg:left-16 w-16 h-16 lg:w-24 lg:h-24 text-white/25 pointer-events-none z-20" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg" stroke="currentColor" stroke-width="1.2">
                <path d="M50 12 C52 4, 58 2, 58 10 C64 4, 72 8, 70 18 C80 22, 78 32, 70 34 C74 44, 66 54, 58 50 C54 58, 46 58, 42 50 C34 54, 26 44, 30 34 C22 32, 20 22, 30 18 C28 8, 36 4, 42 10 C42 2, 48 4, 50 12 Z"/>
                <circle cx="50" cy="30" r="3.5"/>
                <path d="M50 50 L48 72 M42 54 L28 70 M58 54 L72 70"/>
            </svg>

            {{-- Coin haut-droit — feuille line-art --}}
            <svg class="absolute top-24 right-8 lg:top-32 lg:right-20 w-14 h-20 lg:w-20 lg:h-28 text-white/20 pointer-events-none z-20 -rotate-6" viewBox="0 0 60 110" fill="none" stroke="currentColor" stroke-width="1.3">
                <path d="M30 4 C12 14, 0 46, 10 86 C14 100, 30 106, 36 94 C40 104, 52 106, 58 92 C64 72, 58 40, 44 22 C38 8, 30 2, 30 4 Z"/>
                <path d="M30 16 C28 42, 24 64, 18 86" stroke-width="1"/>
                <path d="M30 34 C38 38, 44 48, 46 58" stroke-width="1"/>
                <path d="M30 56 C22 62, 18 72, 16 80" stroke-width="1"/>
            </svg>

            {{-- Coin bas-gauche — petite branche line-art (3 feuilles) --}}
            <svg class="absolute bottom-44 left-4 lg:bottom-52 lg:left-12 w-20 h-16 lg:w-28 lg:h-20 text-white/20 pointer-events-none z-20 rotate-[-8deg]" viewBox="0 0 140 90" fill="none" stroke="currentColor" stroke-width="1.2">
                <path d="M4 80 C30 76, 60 64, 90 48 C110 38, 128 24, 136 10"/>
                <path d="M22 74 C16 72, 12 62, 18 56 C26 54, 30 62, 26 70"/>
                <path d="M58 60 C52 58, 48 48, 54 42 C62 40, 66 48, 62 56"/>
                <path d="M98 42 C92 40, 88 30, 94 24 C102 22, 106 30, 102 38"/>
            </svg>

            {{-- =========================================================
                 5. FORME COURBE BLANCHE ORGANIQUE — EN ARRIÈRE-PLAN
                 ========================================================= --}}
            <div class="absolute inset-x-0 bottom-0 z-0 leading-none pointer-events-none">
                <svg class="w-full h-64 md:h-80 lg:h-96" viewBox="0 0 1440 400" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg">
                    <path
                        d="M0,200 C180,340 340,80 560,140 C720,184 840,330 1020,290 C1180,254 1280,150 1440,220 L1440,400 L0,400 Z"
                        fill="#FAFAF8"
                    />
                    <path
                        d="M0,240 C200,360 400,140 620,180 C780,210 920,360 1100,330 C1260,304 1360,210 1440,260"
                        stroke="#C17817" stroke-opacity="0.08" stroke-width="2" fill="none"
                    />
                </svg>
            </div>

            {{-- =========================================================
                 1. NAVBAR FLOTTANTE — PILULE (rounded-full)
                 ========================================================= --}}
            <div class="relative z-50 mx-6 sm:mx-8 lg:mx-auto lg:px-6 max-w-6xl pt-5 sm:pt-6 -mb-2 sm:-mb-3">
                <nav class="bg-white rounded-full shadow-2xl shadow-black/15 border border-white/60">
                    <div class="px-5 sm:px-6 py-3">
                        <div class="flex items-center justify-between h-[40px]">
                            {{-- LOGO --}}
                            <a href="{{ url('/') }}" class="flex items-center gap-2 pl-0.5">
                                <span class="w-7 h-7 sm:w-8 sm:h-8 rounded-full bg-amber-warm flex items-center justify-center shrink-0 shadow-sm shadow-amber-warm/40">
                                    <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4 text-white" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M12 2L14.09 8.26L21 9.27L16 14.14L17.18 21.02L12 17.77L6.82 21.02L8 14.14L3 9.27L9.91 8.26L12 2Z" fill="currentColor"/>
                                    </svg>
                                </span>
                                <span class="font-fraunces font-extrabold text-base sm:text-lg lg:text-xl text-ink tracking-tight whitespace-nowrap">NutriTrace</span>
                            </a>

                            {{-- LIENS CENTRÉS — text-sm, gap réduit --}}
                            <div class="hidden lg:flex items-center gap-8 text-sm text-ink/60">
                                <a href="#catalogue" class="hover:text-ink font-medium transition-colors">Catalogue</a>
                                <a href="#lots" class="hover:text-ink font-medium transition-colors">Lots</a>
                                <a href="#tracabilite" class="hover:text-ink font-medium transition-colors">Traçabilité</a>
                                <a href="#fonctionnement" class="hover:text-ink font-medium transition-colors">Fonctionnement</a>
                            </div>

                            {{-- ICÔNES À DROITE — wrappers w-8 h-8 (~32px), icônes ~18-20px --}}
                            <div class="flex items-center gap-1.5 sm:gap-2 pr-0.5">
                                @auth
                                    <a href="{{ route('dashboard') }}" class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full ring-1 ring-ink/10 transition-colors hover:ring-ink/25" title="Tableau de bord">
                                        <x-user-avatar :user="auth()->user()" size="sm" class="bg-ink/5 text-ink/70" />
                                    </a>
                                    <form method="POST" action="{{ route('logout') }}" class="inline">
                                        @csrf
                                        <button type="submit" class="w-8 h-8 rounded-full bg-ink/5 hover:bg-ink/10 flex items-center justify-center text-ink/70 transition-colors shrink-0" title="Déconnexion">
                                            <svg class="w-[18px] h-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M9 21H5C4.46957 21 3.96086 20.7893 3.58579 20.4142C3.21071 20.0391 3 19.5304 3 19V5C3 4.46957 3.21071 3.96086 3.58579 3.58579C3.96086 3.21071 4.46957 3 5 3H9"/>
                                                <path d="M16 17L21 12L16 7"/>
                                                <path d="M21 12H9"/>
                                            </svg>
                                        </button>
                                    </form>
                                @else
                                    @if (Route::has('login'))
                                        <a href="{{ route('login') }}" class="w-8 h-8 rounded-full bg-ink/5 hover:bg-ink/10 flex items-center justify-center text-ink/70 transition-colors shrink-0" title="Connexion">
                                            <svg class="w-[18px] h-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M20 21V19C20 16.7909 18.2091 15 16 15H8C5.79086 15 4 16.7909 4 19V21"/>
                                                <circle cx="12" cy="7" r="4"/>
                                            </svg>
                                        </a>
                                    @endif
                                    @if (Route::has('register'))
                                        <a href="{{ route('register') }}" class="w-8 h-8 rounded-full bg-ink/5 hover:bg-ink/10 flex items-center justify-center text-ink/70 transition-colors shrink-0" title="Inscription">
                                            <svg class="w-[18px] h-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M16 21V19C16 16.7909 14.2091 15 12 15H5C2.79086 15 1 16.7909 1 19V21"/>
                                                <circle cx="8.5" cy="7" r="4"/>
                                                <path d="M20 8V14M23 11H17"/>
                                            </svg>
                                        </a>
                                    @endif
                                @endif

                                {{-- Icône recherche --}}
                                <button class="hidden sm:flex w-8 h-8 rounded-full bg-ink/5 hover:bg-ink/10 items-center justify-center text-ink/70 transition-colors shrink-0" title="Rechercher">
                                    <svg class="w-[18px] h-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <circle cx="11" cy="11" r="8"/>
                                        <path d="M21 21L16.65 16.65"/>
                                    </svg>
                                </button>

                                {{-- BOUTON ROND ORANGE/AMBRE — Ø 40px (w-10 = 40px) --}}
                                <a href="#demo" class="w-10 h-10 rounded-full bg-amber-warm hover:bg-amber-warm/90 flex items-center justify-center shadow-lg shadow-amber-warm/40 transition-all hover:scale-105 shrink-0 ml-0.5">
                                    <svg class="w-[18px] h-[18px] text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                        <path d="M5 12H19"/>
                                        <path d="M13 5L20 12L13 19"/>
                                    </svg>
                                </a>
                            </div>
                        </div>
                    </div>
                </nav>
            </div>

            {{-- =========================================================
                 CONTENU PRINCIPAL : TEXTE GAUCHE + IMAGE DÉTOURÉE DROITE
                 ========================================================= --}}
            <div class="relative z-10 max-w-7xl mx-auto px-5 sm:px-6 lg:px-8 pt-10 sm:pt-12 lg:pt-16 pb-28 sm:pb-32 lg:pb-40">
                <div class="grid md:grid-cols-2 gap-8 lg:gap-12 items-start">

                    {{-- =========================================================
                         6. TEXTE — aligné à gauche, moitié gauche
                         ========================================================= --}}
                    <div class="md:pt-10 lg:pt-14 xl:pt-20">
                        <h1 class="font-fraunces leading-[1.02] tracking-tight">
                            <span class="block text-4xl sm:text-5xl md:text-[3.4rem] lg:text-6xl xl:text-7xl font-bold text-white">
                                Connaissez votre nourriture.
                            </span>
                            <span class="block text-4xl sm:text-5xl md:text-[3.4rem] lg:text-6xl xl:text-7xl font-bold mt-3" style="color:#C17817">
                                Faites confiance à son parcours.
                            </span>
                        </h1>

                        <p class="mt-7 sm:mt-8 lg:mt-10 text-base sm:text-lg leading-relaxed text-white/80 max-w-lg">
                            Chaque produit raconte son histoire réelle — de la ferme à votre panier, avec les preuves à l'appui. Suivez, analysez, décidez en toute confiance.
                        </p>

                        <div class="mt-9 sm:mt-10 lg:mt-12">
                            <a href="{{ route('register') }}" class="inline-flex items-center gap-2.5 px-8 py-4 bg-amber-warm hover:bg-amber-warm/90 text-white font-semibold rounded-full shadow-xl shadow-amber-warm/25 transition-all duration-200 hover:-translate-y-0.5 hover:shadow-amber-warm/35">
                                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                    <path d="M5 12H19"/>
                                    <path d="M13 5L20 12L13 19"/>
                                </svg>
                                Commencer
                            </a>
                        </div>
                    </div>

                    {{-- =========================================================
                         3. IMAGE DÉTOURÉE DE LÉGUMES (demi droite)
                         ========================================================= --}}
                    <div class="relative md:-ml-4 lg:-ml-6 xl:ml-0">
                        {{--
                            DÉTOURAGE SIMULÉ via clip-path polygon IRRÉGULIER.
                            Forme volontairement asymétrique (haut avec des
                            pics/creux irréguliers, bas large et ouvert) pour
                            casser l'effet "ovale enfermé" et approcher les
                            contours naturels du tas de légumes.

                            EN PRODUCTION : remplacer ce clip-path par un vrai
                            fichier PNG détouré (fond transparent) exporté depuis
                            Photoshop / Figma — le masque CSS deviendra alors
                            inutile et les contours des aliments seront parfaits.
                        --}}
                        <style>
                            .organic-cutout {
                                /* Haut irrégulier (alternance pics/creux), côtés asymétriques, bas large ouvert */
                                -webkit-clip-path: polygon(
                                    0% 55%,
                                    6% 38%,  14% 42%,  18% 26%,  26% 30%,  32% 14%,
                                    42% 22%,  50% 6%,   58% 18%,  66% 10%,  74% 22%,
                                    82% 12%,  90% 30%,  96% 26%,  100% 44%,
                                    98% 60%,  94% 72%,  88% 82%,  80% 88%,  70% 96%,
                                    56% 100%, 44% 98%,  34% 94%,  24% 98%,  14% 92%,
                                    6% 82%,   2% 70%
                                );
                                        clip-path: polygon(
                                    0% 55%,
                                    6% 38%,  14% 42%,  18% 26%,  26% 30%,  32% 14%,
                                    42% 22%,  50% 6%,   58% 18%,  66% 10%,  74% 22%,
                                    82% 12%,  90% 30%,  96% 26%,  100% 44%,
                                    98% 60%,  94% 72%,  88% 82%,  80% 88%,  70% 96%,
                                    56% 100%, 44% 98%,  34% 94%,  24% 98%,  14% 92%,
                                    6% 82%,   2% 70%
                                );
                            }
                            @media (max-width: 767px) {
                                /* Mobile : clip-path simplifié, très peu irrégulier */
                                .organic-cutout {
                                    -webkit-clip-path: polygon(
                                        0% 50%, 8% 30%, 22% 22%, 36% 10%, 52% 18%,
                                        68% 8%,  82% 22%, 94% 32%, 100% 50%,
                                        98% 70%, 90% 86%, 74% 96%, 58% 100%,
                                        40% 98%,  22% 94%, 10% 84%, 2% 68%
                                    );
                                            clip-path: polygon(
                                        0% 50%, 8% 30%, 22% 22%, 36% 10%, 52% 18%,
                                        68% 8%,  82% 22%, 94% 32%, 100% 50%,
                                        98% 70%, 90% 86%, 74% 96%, 58% 100%,
                                        40% 98%,  22% 94%, 10% 84%, 2% 68%
                                    );
                                }
                            }
                        </style>

                        <div class="relative w-full md:h-[500px] lg:h-[580px] xl:h-[640px] h-[360px]">
                            <img
                                src="https://images.unsplash.com/photo-1488459716781-31db52582fe9?auto=format&fit=crop&w=1400&q=88"
                                alt="Panier de légumes et fruits frais bio : tomates, laitue, poivrons, raisin, avocat, concombre"
                                class="organic-cutout absolute inset-0 w-full h-full object-cover object-top md:object-center translate-y-10 sm:translate-y-12 lg:translate-y-16 drop-shadow-[0_35px_45px_rgba(0,0,0,0.28)]"
                            />
                        </div>

                        {{-- =========================================================
                             7. BADGE QR DISCRET — étiquette suspendue sur l'image
                             ========================================================= --}}
                        <div class="absolute z-40 top-8 lg:top-14 right-0 sm:right-4 lg:right-8 rotate-[-10deg]">
                            <div class="relative bg-white rounded-xl shadow-2xl shadow-black/30 border border-white/70 px-3.5 py-3 w-[170px]">
                                {{-- Petit "fil" simulant une étiquette suspendue --}}
                                <div class="absolute -top-2.5 left-1/2 -translate-x-1/2 w-10 h-3 rounded-t-full border-x-2 border-t-2 border-amber-warm/40 border-b-0"></div>
                                <div class="flex items-center gap-3">
                                    {{-- QR stylisé --}}
                                    <div class="w-12 h-12 shrink-0 grid grid-cols-6 gap-[1.5px] bg-white p-0.5 rounded-md ring-1 ring-ink/5">
                                        @php
                                            $qrP = [0,1,2,3,4,5,6,11,12,17,19,20,21,24,25,29,30,31,32,33,34,35];
                                        @endphp
                                        @for ($p = 0; $p < 36; $p++)
                                            <div class="{{ in_array($p, $qrP) ? 'bg-ink' : 'bg-transparent' }} rounded-[0.5px]"></div>
                                        @endfor
                                    </div>
                                    <div class="leading-tight">
                                        <p class="text-[8.5px] font-black uppercase tracking-[0.14em] text-amber-warm">NutriTrace</p>
                                        <p class="text-[10px] font-semibold text-ink leading-snug mt-1">Scanner pour<br>voir l'origine</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Petite ombre diffuse sous le panier pour renforcer le détourage --}}
                        <div class="absolute -bottom-4 left-1/2 -translate-x-1/2 w-3/4 h-6 bg-black/20 blur-3xl rounded-full z-0"></div>
                    </div>
                </div>
            </div>
        </section>

        {{-- ==============================================================
             A. CATÉGORIES — données réelles (catégories actives uniquement)
             ============================================================== --}}
        <section id="categories" class="bg-cream py-20 lg:py-24">
            <div class="max-w-7xl mx-auto px-6 lg:px-8">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between mb-10">
                    <div>
                        <p class="font-fraunces text-amber-warm font-semibold text-sm tracking-widest uppercase mb-3">Catalogue vivant</p>
                        <h2 class="font-fraunces font-bold text-3xl lg:text-4xl leading-tight text-ink">Explorez par catégorie</h2>
                    </div>
                    <a href="{{ route('front.products.index') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-forest hover:text-amber-warm transition-colors">
                        Tout le catalogue
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12H19"/><path d="M13 5L20 12L13 19"/></svg>
                    </a>
                </div>

                @if ($categories->isEmpty())
                    <div class="rounded-3xl border border-dashed border-ink/15 bg-white px-6 py-14 text-center">
                        <h3 class="font-fraunces text-xl font-bold text-ink">Aucune catégorie pour le moment</h3>
                        <p class="mt-2 text-sm text-ink/55">Les catégories publiées apparaîtront ici automatiquement.</p>
                    </div>
                @else
                    <div class="flex gap-4 overflow-x-auto pb-3 snap-x snap-mandatory sidebar-scrollbar-hidden">
                        @foreach ($categories as $category)
                            <a href="{{ route('front.products.index', ['category' => $category->slug]) }}"
                               class="group snap-start shrink-0 w-[220px] rounded-3xl bg-white border border-ink/8 p-5 shadow-sm hover:-translate-y-1 hover:shadow-lg transition-all duration-200">
                                <span class="w-12 h-12 rounded-2xl bg-amber-warm/10 text-amber-warm font-fraunces font-bold text-xl flex items-center justify-center">
                                    {{ mb_strtoupper(mb_substr($category->name, 0, 1)) }}
                                </span>
                                <p class="mt-4 font-fraunces font-bold text-lg text-ink group-hover:text-forest transition-colors">{{ $category->name }}</p>
                                <p class="mt-1 text-xs text-ink/50">{{ $category->products_count }} produit{{ $category->products_count > 1 ? 's' : '' }} vérifié{{ $category->products_count > 1 ? 's' : '' }}</p>
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>
        </section>

        {{-- ==============================================================
             B. PRODUITS — carrousel des derniers produits vérifiés
             ============================================================== --}}
        <section id="catalogue" class="bg-white py-20 lg:py-24">
            <div class="max-w-7xl mx-auto px-6 lg:px-8" x-data>
                <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between mb-10">
                    <div>
                        <p class="font-fraunces text-amber-warm font-semibold text-sm tracking-widest uppercase mb-3">Produits vérifiés</p>
                        <h2 class="font-fraunces font-bold text-3xl lg:text-4xl leading-tight text-ink">Derniers produits publiés</h2>
                    </div>
                    @if ($products->isNotEmpty())
                        <div class="hidden sm:flex items-center gap-2">
                            <button type="button" @click="$refs.track.scrollBy({ left: -360, behavior: 'smooth' })" aria-label="Produits précédents"
                                class="w-11 h-11 rounded-full bg-cream border border-ink/10 flex items-center justify-center text-ink/70 hover:bg-forest hover:text-white transition-colors">
                                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M19 12H5"/><path d="M11 5L4 12L11 19"/></svg>
                            </button>
                            <button type="button" @click="$refs.track.scrollBy({ left: 360, behavior: 'smooth' })" aria-label="Produits suivants"
                                class="w-11 h-11 rounded-full bg-cream border border-ink/10 flex items-center justify-center text-ink/70 hover:bg-forest hover:text-white transition-colors">
                                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12H19"/><path d="M13 5L20 12L13 19"/></svg>
                            </button>
                        </div>
                    @endif
                </div>

                @if ($products->isEmpty())
                    <div class="rounded-3xl border border-dashed border-ink/15 bg-cream px-6 py-14 text-center">
                        <h3 class="font-fraunces text-xl font-bold text-ink">Aucun produit publié pour le moment</h3>
                        <p class="mt-2 text-sm text-ink/55">Les produits vérifiés apparaîtront ici automatiquement.</p>
                    </div>
                @else
                    <div x-ref="track" class="flex gap-6 overflow-x-auto pb-4 snap-x snap-mandatory scroll-smooth sidebar-scrollbar-hidden">
                        @foreach ($products as $product)
                            <article class="group snap-start shrink-0 w-[280px] sm:w-[320px] rounded-3xl bg-cream border border-ink/8 overflow-hidden shadow-sm hover:shadow-lg hover:-translate-y-1 transition-all duration-200">
                                <a href="{{ route('front.products.show', $product) }}" class="block">
                                    <div class="relative h-48 bg-forest/5 overflow-hidden">
                                        @if ($product->image_url)
                                            <img src="{{ $product->image_url }}" alt="{{ $product->name }}" loading="lazy" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                                        @else
                                            <span class="absolute inset-0 flex items-center justify-center font-fraunces text-6xl font-semibold text-forest/15">{{ mb_strtoupper(mb_substr($product->name, 0, 1)) }}</span>
                                        @endif
                                        @if ($product->carbon_footprint !== null)
                                            <span class="absolute top-3 right-3 rounded-full bg-forest/90 backdrop-blur text-white text-[11px] font-semibold px-2.5 py-1">CO₂ {{ number_format((float) $product->carbon_footprint, 2, ',', ' ') }} kg</span>
                                        @endif
                                    </div>
                                    <div class="p-5">
                                        <span class="inline-block rounded-full bg-forest/10 text-forest text-[11px] font-semibold px-2.5 py-1">{{ $product->categoryModel?->name ?? $product->category }}</span>
                                        <h3 class="mt-3 font-fraunces font-bold text-lg text-ink leading-snug line-clamp-2 group-hover:text-forest transition-colors">{{ $product->name }}</h3>
                                        <p class="mt-2 text-sm text-ink/60 line-clamp-2 leading-6">{{ $product->description ?: 'Tracez ce produit de l\'origine à la distribution.' }}</p>
                                    </div>
                                </a>
                            </article>
                        @endforeach
                    </div>
                @endif
            </div>
        </section>

        {{-- 3. SECTION "VÉRIFIÉ À LA SOURCE" --}}
        <section id="fonctionnement" class="bg-cream py-20 lg:py-28">
            <div class="max-w-7xl mx-auto px-6 lg:px-8">
                <div class="grid md:grid-cols-2 gap-12 lg:gap-20 items-center">
                    <div class="order-2 md:order-1">
                        <div class="relative mx-auto w-80 h-80 lg:w-96 lg:h-96">
                            {{-- Cercle illustratif avec formes géométriques --}}
                            <div class="absolute inset-0 rounded-full border-2 border-ink/10"></div>
                            <div class="absolute inset-8 rounded-full border-2 border-ink/5"></div>

                            {{-- Formes intérieures --}}
                            <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-28 h-28 rounded-3xl bg-amber-warm/15 border border-amber-warm/30 flex items-center justify-center">
                                <svg class="w-12 h-12 text-amber-warm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                    <path d="M20 7L9 18L4 13"/>
                                </svg>
                            </div>

                            <div class="absolute top-10 left-1/2 -translate-x-1/2 w-14 h-14 rounded-2xl bg-emerald-500/15 border border-emerald-500/25 flex items-center justify-center rotate-12">
                                <svg class="w-7 h-7 text-emerald-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                    <path d="M12 2L12 22"/>
                                    <path d="M2 12L22 12"/>
                                    <circle cx="12" cy="12" r="10"/>
                                </svg>
                            </div>

                            <div class="absolute bottom-14 left-10 w-16 h-16 rounded-2xl bg-forest/10 border border-forest/20 flex items-center justify-center -rotate-6">
                                <svg class="w-8 h-8 text-forest" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                    <path d="M4 7L12 3L20 7L20 17L12 21L4 17L4 7Z"/>
                                </svg>
                            </div>

                            <div class="absolute bottom-20 right-8 w-12 h-12 rounded-full bg-sky-500/15 border border-sky-500/25 flex items-center justify-center rotate-45">
                                <svg class="w-6 h-6 text-sky-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                    <path d="M3 12L12 3L21 12L12 21L3 12Z"/>
                                </svg>
                            </div>

                            <div class="absolute top-28 right-2 w-10 h-10 rounded-xl bg-rose-500/10 border border-rose-500/20 flex items-center justify-center -rotate-12">
                                <svg class="w-5 h-5 text-rose-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                    <path d="M6 2L3 6V20H21V6L18 2H6Z"/>
                                    <path d="M3 6H21"/>
                                </svg>
                            </div>

                            <div class="absolute top-32 left-6 w-9 h-9 rounded-lg bg-violet-500/10 border border-violet-500/20 flex items-center justify-center rotate-45">
                                <svg class="w-4 h-4 text-violet-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                    <path d="M12 2L14.09 8.26L21 9.27L16 14.14L17.18 21.02L12 17.77L6.82 21.02L8 14.14L3 9.27L9.91 8.26L12 2Z"/>
                                </svg>
                            </div>

                            {{-- Points de connexion --}}
                            <div class="absolute top-1/2 left-0 w-3 h-3 rounded-full bg-amber-warm"></div>
                            <div class="absolute top-1/2 right-0 w-3 h-3 rounded-full bg-amber-warm"></div>
                            <div class="absolute left-1/2 top-0 w-3 h-3 rounded-full bg-amber-warm"></div>
                            <div class="absolute left-1/2 bottom-0 w-3 h-3 rounded-full bg-amber-warm"></div>
                        </div>
                    </div>

                    <div class="order-1 md:order-2">
                        <h2 class="font-fraunces font-bold text-4xl lg:text-5xl leading-tight text-ink mb-6">
                            Chaque produit peut être vérifié à la source
                        </h2>
                        <p class="text-lg leading-relaxed text-ink/70 mb-10">
                            De la déclaration du producteur jusqu'au scan en magasin, NutriTrace construit un dossier vérifiable pour chaque lot. Sans blockchain ni microservices lourds : sur une base Laravel solide, structurée et maintenable.
                        </p>

                        <div class="grid grid-cols-2 gap-6">
                            <div class="border-l-2 border-amber-warm/60 pl-4 py-1">
                                <p class="font-fraunces font-bold text-3xl text-ink">1 240</p>
                                <p class="text-sm text-ink/60 mt-1">Produits tracés</p>
                            </div>
                            <div class="border-l-2 border-amber-warm/60 pl-4 py-1">
                                <p class="font-fraunces font-bold text-3xl text-ink">312</p>
                                <p class="text-sm text-ink/60 mt-1">Certifications vérifiées</p>
                            </div>
                            <div class="border-l-2 border-amber-warm/60 pl-4 py-1">
                                <p class="font-fraunces font-bold text-3xl text-ink">91<span class="text-amber-warm">%</span></p>
                                <p class="text-sm text-ink/60 mt-1">Confiance moyenne</p>
                            </div>
                            <div class="border-l-2 border-amber-warm/60 pl-4 py-1">
                                <p class="font-fraunces font-bold text-3xl text-ink">340<span class="text-amber-warm"> t</span></p>
                                <p class="text-sm text-ink/60 mt-1">CO<sub>2</sub> suivies</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- 4. SECTION "CAS D'USAGE" --}}
        <section id="certifications" class="bg-forest-dark py-20 lg:py-28 text-white">
            <div class="max-w-7xl mx-auto px-6 lg:px-8">
                <div class="grid md:grid-cols-2 gap-12 lg:gap-16 items-center">
                    <div>
                        <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-white/5 border border-white/10 text-xs tracking-wide text-white/70 mb-8">
                            Cas d'usage — vérification carbone
                        </div>
                        <h2 class="font-fraunces font-bold text-4xl lg:text-5xl leading-tight mb-6">
                            Une allégation bio ne doit jamais rester invérifiable
                        </h2>
                        <p class="text-lg leading-relaxed text-white/75 mb-10 max-w-lg">
                            L'empreinte carbone déclarée est comparée aux données réelles de transport et de production à chaque étape. Tout écart significatif déclenche un signalement avant que le produit n'atteigne le consommateur.
                        </p>

                        <div class="grid grid-cols-3 gap-6">
                            <div>
                                <p class="font-fraunces font-bold text-2xl lg:text-3xl text-white">94<span class="text-amber-warm">%</span></p>
                                <p class="text-xs text-white/60 mt-1">Allégations conformes</p>
                            </div>
                            <div>
                                <p class="font-fraunces font-bold text-2xl lg:text-3xl text-white">0.35<span class="text-amber-warm text-lg"> kg</span></p>
                                <p class="text-xs text-white/60 mt-1">CO<sub>2</sub> déclaré</p>
                            </div>
                            <div>
                                <p class="font-fraunces font-bold text-2xl lg:text-3xl text-white">0.40<span class="text-amber-warm text-lg"> kg</span></p>
                                <p class="text-xs text-white/60 mt-1">Seuil d'écart toléré</p>
                            </div>
                        </div>
                    </div>

                    {{-- Carte simulateur blanche --}}
                    <div class="bg-cream rounded-3xl p-8 shadow-2xl shadow-black/30 border border-ink/5">
                        <p class="text-[11px] font-bold tracking-widest uppercase text-ink/40 mb-2">Simulateur</p>
                        <h3 class="font-fraunces font-bold text-xl text-ink mb-6">Écart carbone déclaré</h3>

                        <div class="mb-6">
                            <p class="text-xs text-ink/60 mb-3">Sélectionnez un écart par rapport à la valeur mesurée</p>
                            <div class="grid grid-cols-5 gap-2" id="sim-buttons">
                                <button type="button" data-value="-10" class="sim-btn py-3 px-2 rounded-xl text-sm font-semibold border-2 border-ink/10 text-ink/70 hover:border-amber-warm hover:bg-amber-warm/5 transition-all duration-200">-10%</button>
                                <button type="button" data-value="-5" class="sim-btn py-3 px-2 rounded-xl text-sm font-semibold border-2 border-ink/10 text-ink/70 hover:border-amber-warm hover:bg-amber-warm/5 transition-all duration-200">-5%</button>
                                <button type="button" data-value="0" class="sim-btn active py-3 px-2 rounded-xl text-sm font-semibold border-2 border-amber-warm bg-amber-warm text-white transition-all duration-200">0%</button>
                                <button type="button" data-value="5" class="sim-btn py-3 px-2 rounded-xl text-sm font-semibold border-2 border-ink/10 text-ink/70 hover:border-amber-warm hover:bg-amber-warm/5 transition-all duration-200">+5%</button>
                                <button type="button" data-value="15" class="sim-btn py-3 px-2 rounded-xl text-sm font-semibold border-2 border-ink/10 text-ink/70 hover:border-amber-warm hover:bg-amber-warm/5 transition-all duration-200">+15%</button>
                            </div>
                        </div>

                        <div class="mb-6">
                            <p class="text-xs text-ink/60 mb-2">Valeur déclarée</p>
                            <div class="flex items-baseline gap-2">
                                <span id="sim-declared" class="font-fraunces font-bold text-4xl text-ink">0.350</span>
                                <span class="text-ink/60">kg CO<sub>2</sub>e / kg</span>
                            </div>
                            <div class="mt-3 h-2 rounded-full bg-ink/5 overflow-hidden">
                                <div id="sim-bar" class="h-full rounded-full bg-emerald-500 transition-all duration-300 ease-out" style="width: 0%"></div>
                            </div>
                            <div class="flex justify-between mt-1.5 text-[10px] text-ink/40">
                                <span>0 kg</span>
                                <span>Seuil toléré : 0.400 kg</span>
                                <span>0.5 kg</span>
                            </div>
                        </div>

                        <div id="sim-result" class="rounded-2xl p-5 bg-emerald-500/10 border border-emerald-500/20 transition-all duration-300 ease-out">
                            <div class="flex items-center gap-3">
                                <div id="sim-icon" class="w-10 h-10 rounded-xl bg-emerald-500 flex items-center justify-center transition-all duration-300">
                                    <svg class="w-5 h-5 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                        <path d="M20 6L9 17L4 12"/>
                                    </svg>
                                </div>
                                <div>
                                    <p id="sim-status" class="font-bold text-ink text-base transition-all duration-300">Conforme</p>
                                    <p id="sim-detail" class="text-xs text-ink/60 mt-0.5 transition-all duration-300">Écart dans la marge de tolérance</p>
                                </div>
                            </div>
                        </div>

                        <a href="#verification" class="mt-6 w-full inline-flex items-center justify-center px-6 py-3.5 bg-forest hover:bg-forest/90 text-white font-medium rounded-2xl transition-colors">
                            Voir le fonctionnement
                        </a>
                    </div>
                </div>
            </div>
        </section>

        {{-- 5. SECTION "TROIS STATUTS POSSIBLES" --}}
        <section class="bg-cream py-20 lg:py-28">
            <div class="max-w-7xl mx-auto px-6 lg:px-8">
                <div class="text-center max-w-2xl mx-auto mb-16">
                    <h2 class="font-fraunces font-bold text-4xl lg:text-5xl leading-tight text-ink mb-5">
                        Trois statuts possibles, une preuve à chaque fois
                    </h2>
                    <p class="text-lg text-ink/65 leading-relaxed">
                        Le moteur ne se contente jamais d'afficher un badge. Il montre toujours la preuve documentaire, la donnée source et la règle qui a conduit au statut.
                    </p>
                </div>

                <div class="grid md:grid-cols-3 gap-6 lg:gap-8">
                    {{-- Carte VÉRIFIÉ --}}
                    <div class="group rounded-3xl bg-white border-t-4 border-l border-r border-b border-emerald-500 border-x-ink/5 border-b-ink/5 p-8 hover:shadow-lg hover:shadow-emerald-500/5 transition-all duration-200">
                        <div class="w-14 h-14 rounded-2xl bg-emerald-500/10 flex items-center justify-center mb-6">
                            <svg class="w-7 h-7 text-emerald-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                <path d="M6 2L3 6V20C3 20.5304 3.21071 21.0391 3.58579 21.4142C3.96086 21.7893 4.46957 22 5 22H19C19.5304 22 20.0391 21.7893 20.4142 21.4142C20.7893 21.0391 21 20.5304 21 20V6L18 2H6Z"/>
                                <path d="M3 6H21"/>
                                <path d="M9 13L11 15L15 11"/>
                            </svg>
                        </div>
                        <div class="flex items-center gap-2 mb-4">
                            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                            <h3 class="font-fraunces font-bold text-sm tracking-widest uppercase text-emerald-600">Vérifié</h3>
                        </div>
                        <h4 class="font-fraunces font-bold text-2xl text-ink mb-4">
                            Toutes les preuves concordent
                        </h4>
                        <p class="text-sm leading-relaxed text-ink/65">
                            Les documents de certification, l'origine déclarée, l'itinéraire de transport et l'empreinte carbone mesurée correspondent aux allégations du produit.
                        </p>
                    </div>

                    {{-- Carte SIGNALÉ --}}
                    <div class="group rounded-3xl bg-white border-t-4 border-l border-r border-b border-red-500 border-x-ink/5 border-b-ink/5 p-8 hover:shadow-lg hover:shadow-red-500/5 transition-all duration-200">
                        <div class="w-14 h-14 rounded-2xl bg-red-500/10 flex items-center justify-center mb-6">
                            <svg class="w-7 h-7 text-red-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                <path d="M12 9V13"/>
                                <path d="M12 17H12.01"/>
                                <path d="M10.29 3.86L1.82 18C1.64811 18.299 1.55406 18.6461 1.55001 19.0017C1.54595 19.3573 1.63218 19.7042 1.80045 19.9995C1.96872 20.2947 2.21283 20.5266 2.5011 20.6649C2.78937 20.8031 3.10879 20.841 3.42001 20.87H20.58C20.8912 20.841 21.2106 20.8031 21.4989 20.6649C21.7872 20.5266 22.0313 20.2947 22.1995 19.9995C22.3678 19.7042 22.454 19.3573 22.45 19.0017C22.4459 18.6461 22.3519 18.299 22.18 18L13.71 3.86C13.526 3.54055 13.2545 3.27374 12.9213 3.09418C12.5882 2.91461 12.2101 2.8313 11.829 2.85827C11.4478 2.88524 11.0818 3.02111 10.7621 3.25117C10.4425 3.48124 10.1818 3.79592 10.01 4.16V4.16L10.29 3.86Z"/>
                            </svg>
                        </div>
                        <div class="flex items-center gap-2 mb-4">
                            <span class="w-2 h-2 rounded-full bg-red-500"></span>
                            <h3 class="font-fraunces font-bold text-sm tracking-widest uppercase text-red-500">Signalé</h3>
                        </div>
                        <h4 class="font-fraunces font-bold text-2xl text-ink mb-4">
                            Incohérence détectée
                        </h4>
                        <p class="text-sm leading-relaxed text-ink/65">
                            Divergence entre l'allégation et les données réelles : itinéraire de transport incompatible avec un label "local", certificat expiré, ou empreinte carbone hors seuil.
                        </p>
                    </div>

                    {{-- Carte EN ATTENTE --}}
                    <div class="group rounded-3xl bg-white border-t-4 border-l border-r border-b border-sky-500 border-x-ink/5 border-b-ink/5 p-8 hover:shadow-lg hover:shadow-sky-500/5 transition-all duration-200">
                        <div class="w-14 h-14 rounded-2xl bg-sky-500/10 flex items-center justify-center mb-6">
                            <svg class="w-7 h-7 text-sky-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                <circle cx="12" cy="12" r="10"/>
                                <path d="M12 6V12L16 14"/>
                            </svg>
                        </div>
                        <div class="flex items-center gap-2 mb-4">
                            <span class="w-2 h-2 rounded-full bg-sky-500"></span>
                            <h3 class="font-fraunces font-bold text-sm tracking-widest uppercase text-sky-600">En attente de preuve</h3>
                        </div>
                        <h4 class="font-fraunces font-bold text-2xl text-ink mb-4">
                            Document manquant
                        </h4>
                        <p class="text-sm leading-relaxed text-ink/65">
                            L'allégation est plausible au regard des données disponibles, mais le document justificatif (certificat, facture de transport) est en attente de téléversement ou de validation par un opérateur.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        {{-- ==============================================================
             C. LOTS — lots actifs récents (données réelles)
             ============================================================== --}}
        <section id="lots" class="bg-white py-20 lg:py-24">
            <div class="max-w-7xl mx-auto px-6 lg:px-8">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between mb-10">
                    <div>
                        <p class="font-fraunces text-amber-warm font-semibold text-sm tracking-widest uppercase mb-3">Chaîne d'approvisionnement</p>
                        <h2 class="font-fraunces font-bold text-3xl lg:text-4xl leading-tight text-ink">Lots récemment mis en circulation</h2>
                    </div>
                    <a href="#tracabilite" class="inline-flex items-center gap-2 text-sm font-semibold text-forest hover:text-amber-warm transition-colors">
                        Suivre un lot
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12H19"/><path d="M13 5L20 12L13 19"/></svg>
                    </a>
                </div>

                @php
                    $batchStatusStyles = [
                        'active' => 'bg-emerald-500/10 text-emerald-700',
                        'expired' => 'bg-amber-500/10 text-amber-700',
                        'recalled' => 'bg-red-500/10 text-red-600',
                    ];
                    $batchStatusLabels = ['active' => 'Actif', 'expired' => 'Expiré', 'recalled' => 'Rappelé'];
                @endphp

                @if ($batches->isEmpty())
                    <div class="rounded-3xl border border-dashed border-ink/15 bg-cream px-6 py-14 text-center">
                        <h3 class="font-fraunces text-xl font-bold text-ink">Aucun lot actif pour le moment</h3>
                        <p class="mt-2 text-sm text-ink/55">Les lots actifs apparaîtront ici automatiquement.</p>
                    </div>
                @else
                    <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($batches as $batch)
                            <div class="flex flex-col rounded-3xl bg-cream border border-ink/8 p-6 shadow-sm hover:shadow-lg transition-all duration-200">
                                <div class="flex items-center gap-3">
                                    <div class="w-12 h-12 rounded-2xl overflow-hidden bg-forest/5 shrink-0 flex items-center justify-center">
                                        @if ($batch->product?->image_url)
                                            <img src="{{ $batch->product->image_url }}" alt="{{ $batch->product->name }}" loading="lazy" class="w-full h-full object-cover">
                                        @else
                                            <span class="font-fraunces font-bold text-forest/30">{{ mb_strtoupper(mb_substr($batch->product?->name ?? 'L', 0, 1)) }}</span>
                                        @endif
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="font-fraunces font-bold text-ink truncate">{{ $batch->product?->name ?? 'Produit' }}</p>
                                        <p class="text-xs font-semibold text-ink/45 truncate">{{ $batch->lot_number }}</p>
                                    </div>
                                    <span class="shrink-0 rounded-full px-2.5 py-1 text-[11px] font-semibold {{ $batchStatusStyles[$batch->status] ?? 'bg-ink/10 text-ink/70' }}">
                                        {{ $batchStatusLabels[$batch->status] ?? ucfirst($batch->status) }}
                                    </span>
                                </div>
                                <dl class="mt-5 grid grid-cols-2 gap-3 border-t border-ink/8 pt-5 text-sm">
                                    <div>
                                        <dt class="text-[11px] uppercase tracking-wider text-ink/45">Production</dt>
                                        <dd class="mt-0.5 font-semibold text-ink">{{ $batch->production_date?->format('d/m/Y') ?? '—' }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-[11px] uppercase tracking-wider text-ink/45">Expiration</dt>
                                        <dd class="mt-0.5 font-semibold text-ink">{{ $batch->expiration_date?->format('d/m/Y') ?? '—' }}</dd>
                                    </div>
                                </dl>
                                <a href="{{ route('front.traceability.show', $batch) }}" class="mt-5 inline-flex items-center justify-center gap-2 rounded-2xl bg-forest px-4 py-2.5 text-sm font-semibold text-white hover:bg-forest-dark transition-colors">
                                    Suivre ce lot
                                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12H19"/><path d="M13 5L20 12L13 19"/></svg>
                                </a>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </section>

        {{-- ==============================================================
             D. TRAÇABILITÉ PUBLIQUE — recherche par n° de lot + exemple
             ============================================================== --}}
        <section id="tracabilite" class="bg-forest py-20 lg:py-28 text-white">
            <div class="max-w-7xl mx-auto px-6 lg:px-8">
                <div class="grid lg:grid-cols-2 gap-12 lg:gap-16 items-center">
                    <div>
                        <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-white/5 border border-white/10 text-xs tracking-wide text-white/70 mb-6">
                            Traçabilité publique — accès libre
                        </div>
                        <h2 class="font-fraunces font-bold text-4xl lg:text-5xl leading-tight mb-6">
                            Suivez un produit en un clic
                        </h2>
                        <p class="text-lg leading-relaxed text-white/75 mb-8 max-w-lg">
                            Saisissez le numéro de lot figurant sur l'étiquette pour visualiser le parcours réel du produit, de la production à la distribution.
                        </p>

                        <form method="GET" action="{{ route('front.traceability.index') }}" class="flex flex-col sm:flex-row gap-3 max-w-lg">
                            <input
                                type="text"
                                name="lot"
                                required
                                placeholder="N° de lot — ex. LOT-{{ date('Y') }}-OLIV-001"
                                class="w-full flex-1 rounded-2xl border border-white/15 bg-white/10 px-4 py-3.5 text-sm font-medium text-white placeholder-white/45 focus:border-amber-warm focus:outline-none focus:ring-2 focus:ring-amber-warm/40"
                            >
                            <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-2xl bg-amber-warm px-6 py-3.5 text-sm font-semibold text-white shadow-lg shadow-amber-warm/25 transition-colors hover:bg-amber-warm/90">
                                Suivre
                                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12H19"/><path d="M13 5L20 12L13 19"/></svg>
                            </button>
                        </form>

                        {{-- Schéma simplifié du parcours --}}
                        <div class="mt-8 flex flex-wrap items-center gap-x-2 gap-y-2 text-sm text-white/70">
                            @foreach (['Production', 'Transport', 'Stockage', 'Distribution'] as $stage)
                                <span class="inline-flex items-center gap-2 rounded-full bg-white/5 border border-white/10 px-3 py-1.5">
                                    <span class="w-1.5 h-1.5 rounded-full bg-amber-warm"></span>
                                    {{ $stage }}
                                </span>
                                @if (! $loop->last)
                                    <svg class="w-3.5 h-3.5 text-white/25" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12H19"/><path d="M13 5L20 12L13 19"/></svg>
                                @endif
                            @endforeach
                        </div>
                    </div>

                    {{-- Exemple de timeline (dernier lot avec événements) --}}
                    <div>
                        @if ($featuredTimeline)
                            <div class="rounded-3xl bg-cream p-6 lg:p-8 shadow-2xl shadow-black/30">
                                <div class="flex flex-wrap items-center justify-between gap-3">
                                    <div>
                                        <p class="text-[11px] font-bold uppercase tracking-widest text-amber-warm">Exemple — dernier lot suivi</p>
                                        <p class="mt-1 font-fraunces font-bold text-lg text-ink">{{ $featuredTimeline->product?->name ?? 'Produit' }}</p>
                                        <p class="text-xs font-semibold text-ink/50">{{ $featuredTimeline->lot_number }}</p>
                                    </div>
                                    <a href="{{ route('front.traceability.show', $featuredTimeline) }}" class="rounded-2xl bg-forest px-4 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-forest-dark">
                                        Ouvrir
                                    </a>
                                </div>
                                <div class="mt-6">
                                    @include('front.partials.timeline', ['batch' => $featuredTimeline, 'compact' => true])
                                </div>
                            </div>
                        @else
                            <div class="rounded-3xl bg-cream p-6 lg:p-8 shadow-2xl shadow-black/30">
                                <p class="text-[11px] font-bold uppercase tracking-widest text-amber-warm">Comment ça marche</p>
                                <ol class="mt-5 space-y-4">
                                    @foreach ([
                                        ['1', 'Chaque lot reçoit un numéro unique', 'Le producteur déclare le lot et chaque étape ajoute un événement daté.'],
                                        ['2', 'Les événements sont horodatés et géolocalisés', 'Production, transformation, transport, stockage et distribution.'],
                                        ['3', 'Le public consulte le parcours en libre accès', 'Aucun compte requis : le numéro de lot suffit.'],
                                    ] as $step)
                                        <li class="flex gap-4">
                                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-amber-warm text-sm font-bold text-white">{{ $step[0] }}</span>
                                            <div>
                                                <p class="font-semibold text-ink">{{ $step[1] }}</p>
                                                <p class="mt-0.5 text-sm text-ink/55">{{ $step[2] }}</p>
                                            </div>
                                        </li>
                                    @endforeach
                                </ol>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </section>

        {{-- 6. SECTION "MOTEUR DE VÉRIFICATION EXPLICABLE" --}}
        <section id="verification" class="bg-cream pb-20 lg:pb-28">
            <div class="max-w-7xl mx-auto px-6 lg:px-8">
                <div class="mb-14">
                    <p class="font-fraunces text-amber-warm font-semibold text-sm tracking-widest uppercase mb-4">Moteur de vérification explicable</p>
                    <h2 class="font-fraunces font-bold text-3xl lg:text-4xl leading-tight text-ink max-w-3xl">
                        Un moteur à règles pondérées, explicable à chaque étape — pas un modèle black-box.
                    </h2>
                </div>

                <div class="grid md:grid-cols-5 gap-6">
                    {{-- Grande carte sombre --}}
                    <div class="md:col-span-3 rounded-3xl bg-forest text-white p-8 lg:p-10">
                        <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-white/5 border border-white/10 text-xs tracking-wide text-white/70 mb-6">
                            <svg class="w-4 h-4 text-amber-warm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M14 7L21 7L21 14"/>
                                <path d="M10 17L3 17L3 10"/>
                                <path d="M21 7L13 15L8 10L3 15"/>
                            </svg>
                            Système de règles pondérées
                        </div>
                        <h3 class="font-fraunces font-bold text-2xl lg:text-3xl leading-tight mb-5">
                            Certifications, transport et empreinte combinés en un statut de vérification
                        </h3>
                        <p class="leading-relaxed text-white/70 mb-8">
                            Chaque facteur est pondéré et visible : validité du certificat bio (30%), cohérence de l'origine géographique (25%), concordance des événements de transport (25%), empreinte carbone dans les seuils (20%). Un opérateur peut comprendre en quelques secondes pourquoi un produit est vérifié, signalé ou en attente — et ajuster les pondérations si nécessaire.
                        </p>

                        <div class="space-y-3.5">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="w-2 h-2 rounded-full bg-emerald-400"></div>
                                    <span class="text-sm text-white/80">Certification AB valide</span>
                                </div>
                                <div class="flex items-center gap-3">
                                    <div class="w-40 h-1.5 rounded-full bg-white/10 overflow-hidden">
                                        <div class="h-full w-[100%] bg-emerald-400 rounded-full"></div>
                                    </div>
                                    <span class="text-xs font-bold text-white/90 w-10 text-right">30%</span>
                                </div>
                            </div>
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="w-2 h-2 rounded-full bg-emerald-400"></div>
                                    <span class="text-sm text-white/80">Origine < 150 km</span>
                                </div>
                                <div class="flex items-center gap-3">
                                    <div class="w-40 h-1.5 rounded-full bg-white/10 overflow-hidden">
                                        <div class="h-full w-[100%] bg-emerald-400 rounded-full"></div>
                                    </div>
                                    <span class="text-xs font-bold text-white/90 w-10 text-right">25%</span>
                                </div>
                            </div>
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="w-2 h-2 rounded-full bg-amber-warm"></div>
                                    <span class="text-sm text-white/80">Trajet transport — 1 événement manquant</span>
                                </div>
                                <div class="flex items-center gap-3">
                                    <div class="w-40 h-1.5 rounded-full bg-white/10 overflow-hidden">
                                        <div class="h-full w-[70%] bg-amber-warm rounded-full"></div>
                                    </div>
                                    <span class="text-xs font-bold text-white/90 w-10 text-right">25%</span>
                                </div>
                            </div>
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="w-2 h-2 rounded-full bg-emerald-400"></div>
                                    <span class="text-sm text-white/80">CO₂ : 0.340 kg / seuil 0.400 kg</span>
                                </div>
                                <div class="flex items-center gap-3">
                                    <div class="w-40 h-1.5 rounded-full bg-white/10 overflow-hidden">
                                        <div class="h-full w-[85%] bg-emerald-400 rounded-full"></div>
                                    </div>
                                    <span class="text-xs font-bold text-white/90 w-10 text-right">20%</span>
                                </div>
                            </div>

                            <div class="mt-6 pt-6 border-t border-white/10 flex items-center justify-between">
                                <div>
                                    <p class="text-xs text-white/50 mb-1">Score final</p>
                                    <p class="font-fraunces font-bold text-4xl">91<span class="text-amber-warm text-2xl">%</span></p>
                                </div>
                                <div class="px-5 py-2.5 rounded-2xl bg-emerald-500/15 border border-emerald-500/30 text-emerald-300 font-bold text-sm">
                                    VÉRIFIÉ
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Deux petites cartes à droite --}}
                    <div class="md:col-span-2 flex flex-col gap-6">
                        <div class="rounded-3xl bg-white border border-ink/5 p-8 hover:shadow-md transition-all duration-200">
                            <div class="w-12 h-12 rounded-2xl bg-amber-warm/10 flex items-center justify-center mb-5">
                                <svg class="w-6 h-6 text-amber-warm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                    <path d="M7 8H17"/>
                                    <path d="M7 12H17"/>
                                    <path d="M7 16H13"/>
                                    <path d="M3 3H21V21H3V3Z"/>
                                </svg>
                            </div>
                            <h4 class="font-fraunces font-bold text-xl text-ink mb-3">OCR d'étiquette</h4>
                            <p class="text-sm leading-relaxed text-ink/65">
                                Extrait automatiquement les certifications, l'origine déclarée et le numéro de lot depuis la photo de l'étiquette produit, puis compare les champs extraits aux données enregistrées dans le dossier de traçabilité.
                            </p>
                        </div>

                        <div class="rounded-3xl bg-white border border-ink/5 p-8 hover:shadow-md transition-all duration-200">
                            <div class="w-12 h-12 rounded-2xl bg-forest/10 flex items-center justify-center mb-5">
                                <svg class="w-6 h-6 text-forest" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                    <path d="M14 2H6C5.46957 2 4.96086 2.21071 4.58579 2.58579C4.21071 2.96086 4 3.46957 4 4V20C4 20.5304 4.21071 21.0391 4.58579 21.4142C4.96086 21.7893 5.46957 22 6 22H18C18.5304 22 19.0391 21.7893 19.4142 21.4142C19.7893 21.0391 20 20.5304 20 20V8L14 2Z"/>
                                    <path d="M14 2V8H20"/>
                                    <path d="M9 13L11 15L15 11"/>
                                    <path d="M9 17H15"/>
                                </svg>
                            </div>
                            <h4 class="font-fraunces font-bold text-xl text-ink mb-3">Vérification documentaire</h4>
                            <p class="text-sm leading-relaxed text-ink/65">
                                Croise les certificats fournis par le producteur avec les référentiels officiels, vérifie les dates de validité, les numéros d'agrément et s'assure que le périmètre du certificat couvre bien le lot concerné.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- 7. BANNIÈRE CTA FINALE --}}
        <section id="demo" class="bg-forest-dark py-20 lg:py-24 text-white">
            <div class="max-w-4xl mx-auto px-6 lg:px-8 text-center">
                <h2 class="font-fraunces font-bold text-4xl lg:text-5xl leading-tight mb-6">
                    Un moteur de vérification qu'on peut toujours justifier
                </h2>
                <p class="text-lg leading-relaxed text-white/75 max-w-2xl mx-auto mb-10">
                    Plutôt qu'une liste de fonctionnalités impressionnantes mais difficiles à démontrer, NutriTrace mise sur un cœur fonctionnel complet — traçabilité, certifications et vérification expliquée, réellement livrable en 7 semaines.
                </p>
                <a href="#" class="inline-flex items-center gap-2 px-8 py-4 bg-amber-warm hover:bg-amber-warm/90 text-white font-semibold rounded-2xl transition-colors text-base">
                    Demander une démo
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M5 12H19"/>
                        <path d="M12 5L19 12L12 19"/>
                    </svg>
                </a>
            </div>
        </section>

        {{-- 8. FOOTER --}}
        <footer class="bg-cream border-t border-ink/5 py-8">
            <div class="max-w-7xl mx-auto px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-between gap-4">
                <p class="text-sm text-ink/45">
                    NutriTrace — cahier des charges, projet Applications Web Avancées 2026/2027
                </p>
                <p class="text-sm text-ink/45">
                    Maquette de landing page
                </p>
            </div>
        </footer>

        {{-- Simulateur JS vanilla --}}
        <script>
            (function () {
                var declaredBase = 0.350;
                var seuil = 0.400;
                var maxBar = 0.5;

                var buttons = document.querySelectorAll('.sim-btn');
                var declaredEl = document.getElementById('sim-declared');
                var barEl = document.getElementById('sim-bar');
                var resultEl = document.getElementById('sim-result');
                var iconEl = document.getElementById('sim-icon');
                var statusEl = document.getElementById('sim-status');
                var detailEl = document.getElementById('sim-detail');

                function setActiveBtn(target) {
                    buttons.forEach(function (btn) {
                        btn.classList.remove('active', 'border-amber-warm', 'bg-amber-warm', 'text-white');
                        btn.classList.add('border-ink/10', 'text-ink/70');
                    });
                    target.classList.add('active', 'border-amber-warm', 'bg-amber-warm', 'text-white');
                    target.classList.remove('border-ink/10', 'text-ink/70');
                }

                function updateSim(percentValue, btn) {
                    setActiveBtn(btn);
                    var value = declaredBase * (1 + percentValue / 100);
                    var percent = Math.min(100, Math.round((value / maxBar) * 100));

                    declaredEl.textContent = value.toFixed(3);
                    barEl.style.width = percent + '%';

                    resultEl.classList.remove(
                        'bg-emerald-500/10', 'border-emerald-500/20',
                        'bg-red-500/10', 'border-red-500/20',
                        'bg-sky-500/10', 'border-sky-500/20'
                    );
                    iconEl.classList.remove('bg-emerald-500', 'bg-red-500', 'bg-sky-500');

                    if (value <= seuil && percentValue < 10) {
                        resultEl.classList.add('bg-emerald-500/10', 'border-emerald-500/20');
                        iconEl.classList.add('bg-emerald-500');
                        iconEl.innerHTML = '<svg class="w-5 h-5 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6L9 17L4 12"/></svg>';
                        statusEl.textContent = 'Conforme';
                        statusEl.classList.remove('text-red-600', 'text-sky-600');
                        statusEl.classList.add('text-ink');
                        detailEl.textContent = 'Écart dans la marge de tolérance';
                        barEl.classList.remove('bg-red-500', 'bg-sky-500');
                        barEl.classList.add('bg-emerald-500');
                    } else if (value > seuil) {
                        resultEl.classList.add('bg-red-500/10', 'border-red-500/20');
                        iconEl.classList.add('bg-red-500');
                        iconEl.innerHTML = '<svg class="w-5 h-5 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 9V13"/><path d="M12 17H12.01"/><path d="M10.29 3.86L1.82 18C1.64811 18.299 1.55406 18.6461 1.55001 19.0017C1.54595 19.3573 1.63218 19.7042 1.80045 19.9995C1.96872 20.2947 2.21283 20.5266 2.5011 20.6649C2.78937 20.8031 3.10879 20.841 3.42001 20.87H20.58C20.8912 20.841 21.2106 20.8031 21.4989 20.6649C21.7872 20.5266 22.0313 20.2947 22.1995 19.9995C22.3678 19.7042 22.454 19.3573 22.45 19.0017C22.4459 18.6461 22.3519 18.299 22.18 18L13.71 3.86Z"/></svg>';
                        statusEl.textContent = 'Écart signalé';
                        statusEl.classList.remove('text-sky-600');
                        statusEl.classList.add('text-red-600');
                        detailEl.textContent = 'Valeur déclarée supérieure au seuil toléré';
                        barEl.classList.remove('bg-emerald-500', 'bg-sky-500');
                        barEl.classList.add('bg-red-500');
                    } else {
                        resultEl.classList.add('bg-sky-500/10', 'border-sky-500/20');
                        iconEl.classList.add('bg-sky-500');
                        iconEl.innerHTML = '<svg class="w-5 h-5 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><path d="M12 6V12L16 14"/></svg>';
                        statusEl.textContent = 'À vérifier';
                        statusEl.classList.remove('text-red-600');
                        statusEl.classList.add('text-sky-600');
                        detailEl.textContent = 'Écart limite — preuve documentaire recommandée';
                        barEl.classList.remove('bg-emerald-500', 'bg-red-500');
                        barEl.classList.add('bg-sky-500');
                    }
                }

                buttons.forEach(function (btn) {
                    btn.addEventListener('click', function () {
                        var val = parseInt(btn.getAttribute('data-value'), 10);
                        updateSim(val, btn);
                    });
                });

                window.addEventListener('load', function () {
                    var def = document.querySelector('.sim-btn[data-value="0"]');
                    if (def) updateSim(0, def);
                });
            })();
        </script>
    </body>
</html>
