<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" x-data="themeManager()" x-init="initTheme()" :class="{ 'dark': isDark }">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'NutriTrace') }} · Workspace</title>
    <script>
        (() => {
            const theme = localStorage.getItem('nutritrace-theme') || 'light';
            const dark = theme === 'dark';
            document.documentElement.classList.toggle('dark', dark);
        })();
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,600;9..144,700;9..144,800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
    <style>
        [data-dashboard-header] h1,
        [data-dashboard-header] h2 {
            color: #1c1c1a;
            font-family: "Fraunces", Georgia, serif;
            font-size: 2rem;
            font-weight: 700;
            line-height: 1.15;
        }

        .dark [data-dashboard-header] h1,
        .dark [data-dashboard-header] h2 { color: #fafaf8; }

        [data-dashboard-header] p:first-child {
            color: #c17817;
            font-size: .68rem;
            font-weight: 700;
            letter-spacing: .18em;
            text-transform: uppercase;
        }
        [data-dashboard-header] p:not(:first-child) {
            margin-top: .35rem !important;
            color: #68736c !important;
            font-size: .875rem !important;
            line-height: 1.35;
        }
        .dark [data-dashboard-header] p:not(:first-child) { color: #b5c0b8 !important; }

        [data-dashboard-header] a[class*="bg-forest"],
        [data-dashboard-header] button[class*="bg-forest"],
        [data-dashboard-header] a[class*="bg-amber"],
        [data-dashboard-header] button[class*="bg-amber"] {
            background-color: #c17817 !important;
            box-shadow: 0 5px 12px rgba(193, 120, 23, .18);
        }
        [data-dashboard-header] a[class*="bg-forest"],
        [data-dashboard-header] a[class*="bg-amber"],
        [data-dashboard-header] button[class*="bg-forest"]:not(:disabled),
        [data-dashboard-header] button[class*="bg-amber"]:not(:disabled) {
            color: #fff !important;
              width: fit-content;
              max-width: 100%;
              align-self: flex-end;
            filter: none !important;
            pointer-events: auto;
        }
        [data-dashboard-header] a[class*="bg-forest"]:hover,
        [data-dashboard-header] button[class*="bg-forest"]:hover,
        [data-dashboard-header] a[class*="bg-amber"]:hover,
        [data-dashboard-header] button[class*="bg-amber"]:hover { background-color: #a76410 !important; }
        [data-dashboard-header] button[class*="bg-forest"]:disabled,
        [data-dashboard-header] button[class*="bg-amber"]:disabled {
            cursor: not-allowed;
            opacity: .6;
            pointer-events: none;
        }

        [data-theme-switch] { background-color: #fafaf8; }
        .dark [data-theme-switch] { background-color: #152a20; }

        .dashboard-content { color: #1c1c1a; }
        .dark .dashboard-content { color: #fafaf8; }
        .dashboard-content > .py-6,
        .dashboard-content > .py-8,
        .dashboard-content > .py-10,
        .dashboard-content > .sm\:py-12 {
            padding-top: 0 !important;
            padding-bottom: 0 !important;
        }
        .dashboard-content [class*="bg-blue-"],
        .dashboard-content [class*="bg-indigo-"],
        .dashboard-content [class*="bg-sky-"] {
            background-color: rgba(31, 61, 46, .1) !important;
        }
        .dashboard-content [class*="text-blue-"],
        .dashboard-content [class*="text-indigo-"],
        .dashboard-content [class*="text-sky-"] { color: #1f3d2e !important; }
        .dashboard-content a[class*="bg-amber-warm"],
        .dashboard-content button[class*="bg-amber-warm"] {
            background-color: #c17817 !important;
            box-shadow: 0 4px 10px rgba(193, 120, 23, .16);
        }
        .dashboard-content a[class*="bg-amber-warm"]:hover,
        .dashboard-content button[class*="bg-amber-warm"]:hover { background-color: #a76410 !important; }
        .dashboard-content table { border-collapse: collapse; }
        .dashboard-content thead { background: rgba(31, 61, 46, .055); }
        .dark .dashboard-content thead { background: #152a20; }
        .dashboard-content thead th {
            border-left: 0 !important;
            border-right: 0 !important;
            color: #536158;
            font-size: .67rem;
            font-weight: 700;
            letter-spacing: .12em;
            text-transform: uppercase;
        }
        .dark .dashboard-content thead th { color: #c3cec6; }
        .dashboard-content tbody tr { border-bottom: 1px solid rgba(31, 61, 46, .09); }
        .dark .dashboard-content tbody tr { border-color: rgba(250, 250, 248, .1); }
        .dashboard-content tbody td { border-left: 0 !important; border-right: 0 !important; }
        .dashboard-content tbody tr:hover { background-color: rgba(193, 120, 23, .045); }
        .dark .dashboard-content tbody tr:hover { background-color: rgba(193, 120, 23, .12); }
        .dashboard-content tbody td:last-child a[href*="/admin/"]:not([href*="/create"]):not(:has(svg)),
        .dashboard-content tbody td:last-child form button[type="submit"]:not(:has(svg)) {
            position: relative;
            display: inline-flex;
            width: 2.25rem;
            height: 2.25rem;
            align-items: center;
            justify-content: center;
            overflow: visible;
            border-radius: 9999px;
            padding: 0 !important;
            font-size: 0 !important;
            text-decoration: none;
        }
        .dashboard-content tbody td:last-child a[href*="/admin/"]:not([href*="/create"]):not(:has(svg))::before,
        .dashboard-content tbody td:last-child form button[type="submit"]:not(:has(svg))::before {
            font-size: 1rem;
            line-height: 1;
        }
        .dashboard-content tbody td:last-child a[href*="/admin/"]:not([href*="/edit"]):not([href*="/create"]):not(:has(svg))::before { content: "\1F441\FE0E"; }
        .dashboard-content tbody td:last-child a[href*="/edit"]:not(:has(svg))::before { content: "\270E"; }
        .dashboard-content tbody td:last-child form button[type="submit"]:not(:has(svg))::before { content: "\1F5D1\FE0E"; }
        .dashboard-content tbody td:last-child a[href*="/admin/"]:not([href*="/create"]):not(:has(svg)):hover::after,
        .dashboard-content tbody td:last-child form button[type="submit"]:not(:has(svg)):hover::after {
            position: absolute;
            z-index: 20;
            bottom: calc(100% + 5px);
            left: 50%;
            border-radius: .35rem;
            background: #152a20;
            padding: .25rem .45rem;
            color: #fafaf8;
            font-size: .68rem;
            font-weight: 600;
            white-space: nowrap;
            transform: translateX(-50%);
        }
        .dashboard-content tbody td:last-child a[href*="/admin/"]:not([href*="/edit"]):not([href*="/create"]):not(:has(svg)):hover::after { content: "Voir"; }
        .dashboard-content tbody td:last-child a[href*="/edit"]:not(:has(svg)):hover::after { content: "Modifier"; }
        .dashboard-content tbody td:last-child form button[type="submit"]:not(:has(svg)):hover::after { content: "Supprimer"; }
        .dark .dashboard-content [class~="bg-white"] { background-color: #1b2923 !important; }
        .dark .dashboard-content input,
        .dark .dashboard-content select,
        .dark .dashboard-content textarea { color: #fafaf8; }
        .dashboard-content nav[role="navigation"] a,
        .dashboard-content nav[role="navigation"] span {
            border-radius: .65rem;
        }
        .dashboard-content nav[role="navigation"] a:hover {
            background: rgba(193, 120, 23, .12);
            color: #1f3d2e;
        }
        .dark .dashboard-content nav[role="navigation"] a { color: #e2e8e3; }
        .dark .dashboard-content nav[role="navigation"] a:hover { background: rgba(193, 120, 23, .18); }
        .dashboard-content nav[role="navigation"] [aria-current="page"] {
            border-color: #c17817 !important;
            background: #c17817 !important;
            color: #152a20 !important;
        }
        .dashboard-content .dashboard-stat-card { border-color: rgba(31, 61, 46, .12); background: #fff; }
        .dark .dashboard-content .dashboard-stat-card { border-color: rgba(250, 250, 248, .1); background: #1b2923; }

        .dark .dashboard-content [class*="text-ink"] { color: #e2e8e3 !important; }
        .dark .dashboard-content [class*="text-green-7"],
        .dark .dashboard-content [class*="text-green-8"],
        .dark .dashboard-content [class*="text-emerald-7"],
        .dark .dashboard-content [class*="text-emerald-8"] { color: #a7f3d0 !important; }
        .dark .dashboard-content [class*="text-amber-8"] { color: #fcd34d !important; }
        .dark .dashboard-content [class*="text-red-7"],
        .dark .dashboard-content [class*="text-red-8"],
        .dark .dashboard-content [class*="text-rose-7"],
        .dark .dashboard-content [class*="text-rose-8"] { color: #fca5a5 !important; }
        .dark .dashboard-content [class*="bg-green-100"],
        .dark .dashboard-content [class*="bg-emerald-100"] { background-color: rgba(45, 106, 79, .28) !important; }
        .dark .dashboard-content [class*="bg-amber-100"] { background-color: rgba(193, 120, 23, .24) !important; }
        .dark .dashboard-content [class*="bg-red-100"],
        .dark .dashboard-content [class*="bg-rose-100"] { background-color: rgba(178, 58, 58, .25) !important; }
        .dark .dashboard-content [class*="bg-blue-"],
        .dark .dashboard-content [class*="bg-indigo-"],
        .dark .dashboard-content [class*="bg-sky-"] { background-color: rgba(31, 61, 46, .35) !important; }
        .dark .dashboard-content [class*="text-blue-"],
        .dark .dashboard-content [class*="text-indigo-"],
        .dark .dashboard-content [class*="text-sky-"] { color: #b7d4c3 !important; }

        @media (max-width: 640px) {
            [data-dashboard-header] h1,
            [data-dashboard-header] h2 { font-size: 1.65rem; }
        }
    </style>
</head>
<body class="bg-cream font-inter text-ink antialiased transition-colors dark:bg-[#101815] dark:text-white">
<div x-data="dashboardShell()" x-init="init()" class="min-h-screen relative">
    <div x-show="sidebarOpen" x-cloak @click="sidebarOpen = false" class="fixed inset-0 z-40 bg-ink/40 lg:hidden"></div>

    <div class="relative min-h-screen bg-cream dark:bg-[#101815]">
        <aside
            data-sidebar
            :class="{ 'translate-x-0': sidebarOpen, '-translate-x-full lg:translate-x-0': !sidebarOpen }"
            class="fixed top-4 left-4 bottom-4 z-50 flex w-60 -translate-x-full flex-col overflow-hidden rounded-[20px] bg-gradient-to-b from-[#16281E] to-[#20402E] text-white shadow-[0_20px_60px_-20px_rgba(22,40,30,0.55)] transition-all duration-300 lg:translate-x-0 dark:shadow-black/50"
            aria-label="Sidebar principal"
        >
            <div x-ref="sidebarScroll" @scroll="saveSidebarScroll($event)" class="sidebar-scrollbar-hidden relative h-full flex flex-col overflow-y-auto p-6">
                <button @click="sidebarOpen = false" class="absolute top-4 right-4 z-10 rounded-xl p-1.5 text-white/50 hover:bg-white/5 lg:hidden dark:text-white/50" aria-label="Fermer le menu">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18M6 6l12 12"/></svg>
                </button>

                <div class="mb-8 flex w-full flex-col items-center gap-2 pt-1">
                    <div class="relative">
                        <div class="h-20 w-20 rounded-full bg-gradient-to-br from-[#2a5040] to-[#16281E] ring-[3px] ring-[#E3A23C] flex items-center justify-center overflow-hidden shadow-[0_10px_25px_-12px_rgba(227,162,60,0.6)]">
                            <span class="font-fraunces text-2xl font-bold text-[#E3A23C]">{{ strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</span>
                        </div>
                    </div>
                    <div class="w-full text-center">
                        <p class="text-sm font-semibold text-white truncate">{{ auth()->user()->name }}</p>
                        <p class="mt-0.5 text-[11px] text-white/50 truncate">{{ auth()->user()->email }}</p>
                    </div>
                </div>

                <nav class="flex-1 space-y-2 pr-1">
                    <div>
                        <ul class="space-y-1">
                            <li>
                                    <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'bg-[#E3A23C] text-[#16281E] font-semibold' : 'text-white/70 hover:bg-white/5' }} group flex items-center gap-3 rounded-xl px-4 py-3 text-sm transition-colors" @click="sidebarOpen = false">
                                    <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/></svg>
                                    <span>Dashboard</span>
                                </a>
                            </li>
                        </ul>
                    </div>

                    @if(auth()->user()->can('manage_products'))
                        <div>
                            <ul class="space-y-1">
                                <li>
                                    <a href="{{ route('admin.products.index') }}" class="{{ request()->routeIs('admin.products.*') ? 'bg-[#E3A23C] text-[#16281E] font-semibold' : 'text-white/70 hover:bg-white/5' }} group flex items-center gap-3 rounded-xl px-4 py-3 text-sm transition-colors" @click="sidebarOpen = false">
                                        <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path d="M12 3c2 3-1 5-3 6 2 2 6 3 3 9-3-2-5-5-6-5-1 2-2 5-5 6 1-3 0-7 3-9-3-2-5-4-3-6 3 1 5 4 5 4 0-3 1-6 6-5Z"/></svg>
                                        <span>Products</span>
                                    </a>
                                </li>
                                <li>
                                    <a href="{{ route('admin.categories.index') }}" class="{{ request()->routeIs('admin.categories.*') ? 'bg-[#E3A23C] text-[#16281E] font-semibold' : 'text-white/70 hover:bg-white/5' }} group flex items-center gap-3 rounded-xl px-4 py-3 text-sm transition-colors" @click="sidebarOpen = false">
                                        <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 5h16v14H4z"/><path d="M8 9h8M8 13h5M8 17h3"/><circle cx="18" cy="16" r="2"/></svg>
                                        <span>Categories</span>
                                    </a>
                                </li>
                                <li>
                                    <a href="{{ route('admin.batches.index') }}" class="{{ request()->routeIs('admin.batches.*') && ! request()->routeIs('admin.batches.traceability') ? 'bg-[#E3A23C] text-[#16281E] font-semibold' : 'text-white/70 hover:bg-white/5' }} group flex items-center gap-3 rounded-xl px-4 py-3 text-sm transition-colors" @click="sidebarOpen = false">
                                        <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path d="M4 8h16v11H4zM4 8l3-4h10l3 4M9 12h6"/></svg>
                                        <span>Batches / Lots</span>
                                    </a>
                                </li>
                            </ul>
                        </div>

                        <div>
                            <ul class="space-y-1">
                                <li>
                                    <a href="{{ route('admin.certifications.index') }}" class="{{ request()->routeIs('admin.certifications.*') ? 'bg-[#E3A23C] text-[#16281E] font-semibold' : 'text-white/70 hover:bg-white/5' }} group flex items-center gap-3 rounded-xl px-4 py-3 text-sm transition-colors" @click="sidebarOpen = false">
                                        <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path d="M12 2l3 3 4-1 1 4 3 3-3 3-1 4-4-1-3 3-3-3-4 1-1-4-3-3 3-3 4 1 1-4z"/><path d="m9 12 2 2 4-4"/></svg>
                                        <span>Certifications</span>
                                    </a>
                                </li>
                                <li>
                                    <a href="{{ route('admin.certifications.intelligence') }}" class="{{ request()->routeIs('admin.certifications.intelligence') ? 'bg-[#E3A23C] text-[#16281E] font-semibold' : 'text-white/70 hover:bg-white/5' }} group flex items-center gap-3 rounded-xl px-4 py-3 text-sm transition-colors" @click="sidebarOpen = false">
                                        <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path d="M12 2l1.8 5.2L19 9l-5.2 1.8L12 16l-1.8-5.2L5 9l5.2-1.8L12 2Z"/><path d="M19 15l.8 2.2L22 18l-2.2.8L19 21l-.8-2.2L16 18l2.2-.8L19 15Z"/></svg>
                                        <span>Certification Intelligence</span>
                                    </a>
                                </li>
                                <li>
                                    <a href="{{ route('account.verification.edit') }}" class="{{ request()->routeIs('account.verification.*') ? 'bg-[#E3A23C] text-[#16281E] font-semibold' : 'text-white/70 hover:bg-white/5' }} group flex items-center gap-3 rounded-xl px-4 py-3 text-sm transition-colors" @click="sidebarOpen = false">
                                        <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><circle cx="12" cy="12" r="9"/><path d="m8 12 3 3 5-6"/></svg>
                                        <span>Verification</span>
                                    </a>
                                </li>
                                <li>
                                    <a href="{{ route('admin.quality-checks.index') }}" class="{{ request()->routeIs('admin.quality-checks.*') ? 'bg-[#E3A23C] text-[#16281E] font-semibold' : 'text-white/70 hover:bg-white/5' }} group flex items-center gap-3 rounded-xl px-4 py-3 text-sm transition-colors" @click="sidebarOpen = false">
                                        <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 5h16v14H4z"/><path d="M8 9h8M8 13h5"/><path d="m16 17 2 2 3-4"/></svg>
                                        <span>Analyses Qualité</span>
                                    </a>
                                </li>
                                <li>
                                    <a href="{{ route('admin.ocr-checks.index') }}" class="{{ request()->routeIs('admin.ocr-checks.*') ? 'bg-[#E3A23C] text-[#16281E] font-semibold' : 'text-white/70 hover:bg-white/5' }} group flex items-center gap-3 rounded-xl px-4 py-3 text-sm transition-colors" @click="sidebarOpen = false">
                                        <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 5h16v14H4z"/><path d="M8 9h8M8 13h5M8 17h3"/><path d="m17 15 2 2 3-4"/></svg>
                                        <span>OCR / Étiquettes</span>
                                    </a>
                                </li>
                            </ul>
                        </div>

                        <div>
                            <ul class="space-y-1">
                                <li>
                                    <a href="{{ route('admin.events.index') }}" class="{{ request()->routeIs('admin.events.*') || request()->routeIs('admin.batches.traceability') ? 'bg-[#E3A23C] text-[#16281E] font-semibold' : 'text-white/70 hover:bg-white/5' }} group flex items-center gap-3 rounded-xl px-4 py-3 text-sm transition-colors" @click="sidebarOpen = false">
                                        <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><circle cx="12" cy="12" r="3"/><circle cx="4" cy="12" r="1.5"/><circle cx="20" cy="12" r="1.5"/><circle cx="12" cy="4" r="1.5"/><circle cx="12" cy="20" r="1.5"/><path d="M5.5 12H9m6 0h3.5M12 5.5V9m0 6v3.5"/></svg>
                                        <span>Traceability</span>
                                    </a>
                                </li>
                                <li>
                                    <a href="{{ route('admin.transport-conditions.index') }}" class="{{ request()->routeIs('admin.transport-conditions.*') ? 'bg-[#E3A23C] text-[#16281E] font-semibold' : 'text-white/70 hover:bg-white/5' }} group flex items-center gap-3 rounded-xl px-4 py-3 text-sm transition-colors" @click="sidebarOpen = false">
                                        <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path d="M12 3v18M5 8h14M5 16h14"/><path d="M7 3h10M7 21h10"/></svg>
                                        <span>Transport Conditions</span>
                                    </a>
                                </li>
                                <li>
                                    <a href="{{ route('admin.alerts.index') }}" class="{{ request()->routeIs('admin.alerts.*') ? 'bg-[#E3A23C] text-[#16281E] font-semibold' : 'text-white/70 hover:bg-white/5' }} group flex items-center gap-3 rounded-xl px-4 py-3 text-sm transition-colors" @click="sidebarOpen = false">
                                        <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path d="m12 3 9 16H3L12 3Z"/><path d="M12 9v4M12 16h.01"/></svg>
                                        <span>Alerts</span>
                                    </a>
                                </li>
                            </ul>
                        </div>

                        @can('manage_stocks')
                        <div>
                            <ul class="space-y-1">
                                <li>
                                    <a href="{{ route('stocks.index') }}" class="{{ request()->routeIs('stocks.*') ? 'bg-[#E3A23C] text-[#16281E] font-semibold' : 'text-white/70 hover:bg-white/5' }} group flex items-center gap-3 rounded-xl px-4 py-3 text-sm transition-colors" @click="sidebarOpen = false">
                                        <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path d="M3 8h18v11H3zM7 8V5h10v3M8 12h8M8 16h6"/></svg>
                                        <span>Stocks</span>
                                    </a>
                                </li>
                                <li>
                                    <a href="{{ route('stock-movements.index') }}" class="{{ request()->routeIs('stock-movements.*') ? 'bg-[#E3A23C] text-[#16281E] font-semibold' : 'text-white/70 hover:bg-white/5' }} group flex items-center gap-3 rounded-xl px-4 py-3 text-sm transition-colors" @click="sidebarOpen = false">
                                        <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path d="M3 12h18"/><path d="M12 3v18"/><path d="M7 7l-4 5 4 5M17 7l4 5-4 5"/></svg>
                                        <span>Stock movements</span>
                                    </a>
                                </li>
                                <li>
                                    <a href="{{ route('sites.index') }}" class="{{ request()->routeIs('sites.*') ? 'bg-[#E3A23C] text-[#16281E] font-semibold' : 'text-white/70 hover:bg-white/5' }} group flex items-center gap-3 rounded-xl px-4 py-3 text-sm transition-colors" @click="sidebarOpen = false">
                                        <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path d="M4 19V7l8-4 8 4v12"/><path d="M9 10h6M9 14h6M12 6v12"/></svg>
                                        <span>Sites</span>
                                    </a>
                                </li>
                                <li>
                                    <a href="{{ route('forecast.index') }}" class="{{ request()->routeIs('forecast.index') ? 'bg-[#E3A23C] text-[#16281E] font-semibold' : 'text-white/70 hover:bg-white/5' }} group flex items-center gap-3 rounded-xl px-4 py-3 text-sm transition-colors" @click="sidebarOpen = false">
                                        <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path d="M4 18h16"/><path d="M7 15l3-3 3 2 5-6"/><path d="M19 8v4h-4"/></svg>
                                        <span>Forecast</span>
                                    </a>
                                </li>
                                <li>
                                    <a href="{{ route('optimization.index') }}" class="{{ request()->routeIs('optimization.*') ? 'bg-[#E3A23C] text-[#16281E] font-semibold' : 'text-white/70 hover:bg-white/5' }} group flex items-center gap-3 rounded-xl px-4 py-3 text-sm transition-colors" @click="sidebarOpen = false">
                                        <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path d="M12 3v18"/><path d="m5 8 7-5 7 5-7 5-7-5Z"/><path d="m5 16 7 5 7-5"/></svg>
                                        <span>Optimization</span>
                                    </a>
                                </li>
                                <li>
                                    <a href="{{ route('simulations.index') }}" class="{{ request()->routeIs('simulations.*') ? 'bg-[#E3A23C] text-[#16281E] font-semibold' : 'text-white/70 hover:bg-white/5' }} group flex items-center gap-3 rounded-xl px-4 py-3 text-sm transition-colors" @click="sidebarOpen = false">
                                        <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><circle cx="12" cy="12" r="8"/><path d="M12 4v16M4 12h16"/><path d="M12 4c2.5 2.2 3.5 5 3.5 8s-1 5.8-3.5 8M12 4c-2.5 2.2-3.5 5-3.5 8s1 5.8 3.5 8"/></svg>
                                        <span>Digital twin</span>
                                    </a>
                                </li>
                                <li>
                                    <a href="{{ route('shelf-life.index') }}" class="{{ request()->routeIs('shelf-life.*') ? 'bg-[#E3A23C] text-[#16281E] font-semibold' : 'text-white/70 hover:bg-white/5' }} group flex items-center gap-3 rounded-xl px-4 py-3 text-sm transition-colors" @click="sidebarOpen = false">
                                        <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path d="M10 13.5V4.5a2 2 0 1 1 4 0v9a4 4 0 1 1-4 0Z"/><path d="M12 9v6"/><path d="M18 5h3M18 9h3"/></svg>
                                        <span>Dynamic shelf life</span>
                                    </a>
                                </li>
                            </ul>
                        </div>
                        @endcan

                        @if(auth()->user()->can('manage_users'))
                            <div>
                                <ul class="space-y-1">
                                    <li>
                                        <a href="{{ route('admin.users.index') }}" class="{{ request()->routeIs('admin.users.*') ? 'bg-[#E3A23C] text-[#16281E] font-semibold' : 'text-white/70 hover:bg-white/5' }} group flex items-center gap-3 rounded-xl px-4 py-3 text-sm transition-colors" @click="sidebarOpen = false">
                                            <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                                            <span>Users</span>
                                        </a>
                                    </li>
                                    <li>
                                        <a href="{{ route('admin.verification.index') }}" class="{{ request()->routeIs('admin.verification.*') ? 'bg-[#E3A23C] text-[#16281E] font-semibold' : 'text-white/70 hover:bg-white/5' }} group flex items-center gap-3 rounded-xl px-4 py-3 text-sm transition-colors" @click="sidebarOpen = false">
                                            <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path d="M9 11V6h6v5"/><path d="M4 8h16v11a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V8z"/><path d="m9 15 2 2 4-4"/></svg>
                                            <span>Verification Requests</span>
                                        </a>
                                    </li>
                                    @if(app('router')->has('admin.audit.index'))
                                        <li>
                                            <a href="{{ route('admin.audit.index') }}" class="{{ request()->routeIs('admin.audit.*') ? 'bg-[#E3A23C] text-[#16281E] font-semibold' : 'text-white/70 hover:bg-white/5' }} group flex items-center gap-3 rounded-xl px-4 py-3 text-sm transition-colors" @click="sidebarOpen = false">
                                                <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path d="M4 4h16v16H4zM8 8h8M8 12h8M8 16h5"/></svg>
                                                <span>Audit Logs</span>
                                            </a>
                                        </li>
                                    @endif
                                </ul>
                            </div>
                        @endif
                    @endif

                    <div>
                        <ul class="space-y-1">
                            <li>
                                <a href="{{ route('profile.edit') }}" class="{{ request()->routeIs('profile.*') ? 'bg-[#E3A23C] text-[#16281E] font-semibold' : 'text-white/70 hover:bg-white/5' }} group flex items-center gap-3 rounded-xl px-4 py-3 text-sm transition-colors" @click="sidebarOpen = false">
                                    <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></svg>
                                    <span>Mon profil</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </nav>

                <div class="mt-auto pt-8 space-y-1">
                    <form method="POST" action="{{ route('logout') }}" class="w-full" @submit.prevent="if(confirm('Se déconnecter de NutriTrace ?')) { $el.submit(); }">
                        @csrf
                        <button type="submit" class="flex w-full items-center gap-3 rounded-xl px-4 py-3 text-sm text-white/70 hover:bg-white/5 transition-colors" @click="sidebarOpen = false">
                            <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="m16 17 5-5-5-5"/><path d="M21 12H9"/></svg>
                            <span>Logout</span>
                        </button>
                    </form>
                </div>
            </div>
        </aside>

        <div class="min-h-screen pl-0 transition-all duration-300 lg:pl-[17rem]">
            <header class="sticky top-0 z-30 bg-cream/90 backdrop-blur dark:bg-[#101815]/90 border-b border-ink/5">
                <div data-dashboard-header class="flex min-h-20 items-center gap-4 px-8 py-6 lg:px-10">
                    <div class="flex min-w-0 flex-1 items-center gap-3">
                        <button @click="sidebarOpen = true" class="rounded-xl p-2 text-ink/60 hover:bg-white lg:hidden dark:text-white/60" aria-label="Ouvrir le menu">
                            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
                        </button>
                        <div class="min-w-0 flex-1">
                            {{ $header ?? '' }}
                        </div>
                    </div>
                    <div class="flex shrink-0 items-center gap-3">
                        @if(isset($headerActions))
                            {{ $headerActions }}
                        @else
                            <a href="{{ route('front.products.index') }}" target="_blank" rel="noopener" class="hidden rounded-xl px-3 py-2 text-sm font-semibold text-ink/55 transition hover:bg-white sm:inline-flex dark:text-white/60 dark:hover:bg-white/5" title="Catalogue public">
                                Catalogue
                            </a>
                            <div class="rounded-full border border-ink/10 p-1 shadow-sm dark:border-white/10">
                                <button type="button" role="switch" @click="theme = theme === 'light' ? 'dark' : 'light'; applyTheme()" class="relative h-8 w-16 rounded-full transition-colors" data-theme-switch :aria-checked="theme === 'dark'" :aria-label="theme === 'dark' ? 'Passer en mode clair' : 'Passer en mode sombre'" title="Changer de thème">
                                    <svg x-cloak x-show="theme === 'dark'" class="absolute left-1.5 h-3 w-3 text-white/60" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2m0 16v2M4.93 4.93l1.42 1.42m11.3 11.3 1.42 1.42M2 12h2m16 0h2M4.93 19.07l1.42-1.42m11.3-11.3 1.42-1.42"/></svg>
                                    <svg x-cloak x-show="theme === 'light'" class="absolute right-1.5 h-3 w-3 text-forest/45" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M20.5 15.5A8.5 8.5 0 0 1 8.5 3.5 8.5 8.5 0 1 0 20.5 15.5Z"/></svg>
                                    <span class="absolute inset-y-1 left-1 grid h-6 w-6 place-items-center rounded-full bg-white text-[#1F3D2E] shadow-md transition-transform duration-200" :class="theme === 'dark' ? 'translate-x-8' : 'translate-x-0'">
                                        <svg x-cloak x-show="theme === 'light'" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2m0 16v2M4.93 4.93l1.42 1.42m11.3 11.3 1.42 1.42M2 12h2m16 0h2M4.93 19.07l1.42-1.42m11.3-11.3 1.42-1.42"/></svg>
                                        <svg x-cloak x-show="theme === 'dark'" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M20.5 15.5A8.5 8.5 0 0 1 8.5 3.5 8.5 8.5 0 1 0 20.5 15.5Z"/></svg>
                                    </span>
                                </button>
                            </div>
                        @endif
                    </div>
                </div>
            </header>

            <main data-dashboard-main class="min-h-[calc(100vh-5rem)]">
                @if(session('success'))
                    <div class="px-6 pt-6 lg:px-10">
                        <div class="max-w-[1440px] mx-auto rounded-xl border border-emerald-500/20 bg-emerald-500/10 px-4 py-3 text-sm font-medium text-emerald-800 dark:text-emerald-300">{{ session('success') }}</div>
                    </div>
                @endif
                @if(session('error'))
                    <div class="px-6 pt-6 lg:px-10">
                        <div class="max-w-[1440px] mx-auto rounded-xl border border-rose-500/20 bg-rose-500/10 px-4 py-3 text-sm font-medium text-rose-800 dark:text-rose-300">{{ session('error') }}</div>
                    </div>
                @endif
                <div class="dashboard-content px-8 pt-8 pb-8">
                    {{ $slot }}
                </div>
            </main>
        </div>
    </div>
</div>
<script>
    function dashboardShell() {
        return {
            sidebarOpen: false,
            init() {
                this.$nextTick(() => {
                    this.$refs.sidebarScroll.scrollTop = Number(localStorage.getItem('nutritrace-sidebar-scroll') || 0);
                });
            },
            saveSidebarScroll(event) {
                localStorage.setItem('nutritrace-sidebar-scroll', String(event.target.scrollTop));
            },
        };
    }

    function themeManager() {
        return {
            theme: 'light',
            isDark: false,
            initTheme() {
                this.theme = localStorage.getItem('nutritrace-theme') || 'light';
                this.applyTheme();
                },
                applyTheme() {
                this.isDark = this.theme === 'dark';
                document.documentElement.classList.toggle('dark', this.isDark);
                localStorage.setItem('nutritrace-theme', this.theme);
            }
        };
    }
</script>
@stack('scripts')
</body>
</html>
