<style>
    :root {
        --mist: #fafaf8;
        --panel: #ffffff;
        --ink: #24352d;
        --ink-soft: #66736b;
        --rule: rgba(31, 61, 46, .1);
        --pine: #1f3d2e;
        --pine-deep: #152a20;
        --pine-wash: rgba(31, 61, 46, .08);
        --amber: #c17817;
        --amber-wash: rgba(193, 120, 23, .1);
        --brick: #b23a3a;
        --brick-wash: rgba(178, 58, 58, .08);
        --sky: #245d8a;
        --sky-wash: #e2edf6;
        --slot: #dde5e2;
        --leaf: #3d7a1a;
        --leaf-wash: #e6f1dc;
        --rose: #a8325e;
        --rose-wash: #f8e3eb;
        --font: "Inter", sans-serif;
    }

    .stock-module { color: var(--ink); }
    .dark .stock-module { color: #fafaf8; }
    .page-head { display: flex; justify-content: space-between; align-items: flex-end; gap: 16px; flex-wrap: wrap; margin-bottom: 24px; }
    .page-head h1 { color: var(--ink); font: 700 1.875rem/1.2 "Fraunces", Georgia, serif; }
    .dark .page-head h1 { color: #fafaf8; }
    .page-head .lede { margin-top: 8px; color: var(--ink-soft); max-width: 62ch; font-size: .9rem; line-height: 1.6; }
    .dark .page-head .lede, .dark .muted, .dark .small { color: #b7c2ba; }
    .back { display: inline-block; margin-bottom: 8px; font-size: 0.9rem; font-weight: 600; text-decoration: none; }
    .actions { display: flex; gap: 8px; flex-wrap: wrap; }

    .btn { display: inline-flex; align-items: center; justify-content: center; gap: 8px; min-height: 42px; padding: 9px 16px; border-radius: 10px; font: 600 .875rem var(--font); border: 1px solid transparent; cursor: pointer; text-decoration: none; transition: background-color .15s, border-color .15s, color .15s; }
    .btn-primary { background: #c17817; color: #fff; box-shadow: 0 4px 12px rgba(193, 120, 23, .18); }
    .btn-primary:hover { background: #a76410; color: #fff; }
    .btn-ghost { background: #fff; color: var(--ink); border-color: rgba(36, 53, 45, .1); }
    .btn-ghost:hover { border-color: rgba(31, 61, 46, .3); color: var(--pine); background: #fafaf8; }
    .dark .btn-ghost { background: #1b2923; border-color: rgba(255,255,255,.12); color: #fafaf8; }
    .dark .btn-ghost:hover { background: rgba(255,255,255,.06); }
    .btn-danger { background: transparent; color: var(--brick); border-color: #e6b7b1; }
    .btn-danger:hover { background: var(--brick-wash); }
    .linklike { background: none; border: 0; padding: 0; font: inherit; color: var(--brick); cursor: pointer; text-decoration: underline; text-underline-offset: 2px; }

    .panel { background: #fff; border: 1px solid rgba(31, 61, 46, .1); border-radius: 16px; padding: 20px 22px; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(31, 61, 46, .06); }
    .dark .panel { background: #1b2923; border-color: rgba(255,255,255,.1); box-shadow: none; }
    .panel-head { display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; flex-wrap: wrap; margin-bottom: 14px; }
    .panel-head p { margin-top: 3px; }
    .panel > .table-wrap { margin: 0 -22px -20px; border-top: 1px solid var(--rule); }

    .figures { display: grid; grid-template-columns: repeat(auto-fit, minmax(190px, 1fr)); gap: 16px; margin-bottom: 20px; }
    .figure { background: #fff; border: 1px solid rgba(31, 61, 46, .1); border-radius: 16px; padding: 18px 20px; box-shadow: 0 1px 3px rgba(31, 61, 46, .06); }
    .dark .figure { background: #1b2923; border-color: rgba(255,255,255,.1); box-shadow: none; }
    .figure strong { display: block; color: var(--ink); font: 700 1.875rem/1.2 "Fraunces", Georgia, serif; font-variant-numeric: tabular-nums; }
    .dark .figure strong { color: #fafaf8; }
    .figure span { display: block; margin-top: 4px; color: var(--ink-soft); font-size: .82rem; }
    .figure.is-alert strong { color: var(--brick); }
    .figure.is-warn strong { color: var(--amber); }

    .table-wrap { overflow-x: auto; }
    table { width: 100%; border-collapse: collapse; }
    th { text-align: left; font-size: .68rem; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; color: rgba(36, 53, 45, .55); padding: 14px 16px; border-bottom: 1px solid rgba(31, 61, 46, .1); background: rgba(31, 61, 46, .045); white-space: nowrap; }
    td { padding: 14px 16px; border-bottom: 1px solid rgba(31, 61, 46, .08); vertical-align: middle; }
    .dark th { color: rgba(250,250,248,.6); border-color: rgba(255,255,255,.1); background: #152a20; }
    .dark td { border-color: rgba(255,255,255,.09); }
    tbody tr:last-child td { border-bottom: 0; }
    tbody tr:hover td { background: rgba(193, 120, 23, .045); }
    .dark tbody tr:hover td { background: rgba(193, 120, 23, .1); }
    tr.is-off td { color: var(--ink-soft); background: rgba(36,53,45,.025); }
    .dark tr.is-off td { color: #aab7ae; background: rgba(255,255,255,.025); }
    .row-actions { white-space: nowrap; text-align: right; }
    .row-actions a, .row-actions form { margin-left: 12px; display: inline; font-size: 0.9rem; }
    .code { font-weight: 600; letter-spacing: 0.01em; }

    .tag { display: inline-flex; align-items: center; gap: 5px; padding: 4px 10px; border-radius: 999px; font-size: .72rem; font-weight: 700; white-space: nowrap; background: rgba(36,53,45,.08); color: #526158; }
    .tag-ok, .tag-accepted, .tag-in { background: rgba(45,106,79,.12); color: #2d6a4f; }
    .tag-low, .tag-expired, .tag-recalled, .tag-rejected, .tag-out { background: rgba(178,58,58,.1); color: #b23a3a; }
    .tag-expiring, .tag-pending, .tag-adjustment { background: rgba(193,120,23,.12); color: #9c5c10; }
    .tag-transfer, .tag-modified { background: rgba(31,61,46,.1); color: #1f3d2e; }
    .tag-rescued { background: rgba(45,106,79,.12); color: #2d6a4f; }
    .dark .tag { background: rgba(255,255,255,.1); color: #e2e8e3; }
    .dark .tag-ok, .dark .tag-accepted, .dark .tag-in, .dark .tag-rescued { background: rgba(45,106,79,.3); color: #a7d8bc; }
    .dark .tag-low, .dark .tag-expired, .dark .tag-recalled, .dark .tag-rejected, .dark .tag-out { background: rgba(178,58,58,.28); color: #ffc1c1; }
    .dark .tag-expiring, .dark .tag-pending, .dark .tag-adjustment { background: rgba(193,120,23,.25); color: #f2c27f; }
    .dark .tag-transfer, .dark .tag-modified { background: rgba(31,61,46,.4); color: #b7d4c3; }
    .impact { display: flex; align-items: center; gap: 14px; flex-wrap: wrap; background: rgba(45,106,79,.08); border: 1px solid rgba(45,106,79,.18); color: #24543f; border-radius: 16px; padding: 16px 18px; margin-bottom: 20px; }
    .dark .impact { background: rgba(45,106,79,.18); border-color: rgba(167,216,188,.15); color: #d6eee0; }
    .impact-icon { font-size: 1.5rem; line-height: 1; }
    .impact p { margin: 0; }
    .impact b { font-variant-numeric: tabular-nums; }
    .impact .muted { color: #4f6d3c; }

    .rack { --fill: var(--pine); position: relative; height: 12px; min-width: 160px; border-radius: 999px; background: rgba(36,53,45,.1); overflow: hidden; }
    .rack > span { position: absolute; inset: 0 auto 0 0; border-radius: 999px; background: var(--fill); }
    .dark .rack { background: rgba(255,255,255,.12); }
    .rack.is-mid { --fill: #c48a1a; }
    .rack.is-high { --fill: var(--brick); }
    .capacity { display: grid; gap: 5px; min-width: 220px; }
    .capacity-legend { display: flex; justify-content: space-between; gap: 12px; font-size: 0.84rem; color: var(--ink-soft); }
    .capacity-legend b { color: var(--ink); font-variant-numeric: tabular-nums; }

    .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 18px 20px; }
    .field { display: grid; gap: 6px; align-content: start; }
    .field.wide { grid-column: 1 / -1; }
    .field[hidden] { display: none; }
    label, .label { font-weight: 600; font-size: 0.9rem; }
    input[type=text], input[type=number], input[type=date], input[type=datetime-local], input[type=search], select, textarea { width: 100%; min-height: 42px; padding: 9px 12px; border: 1px solid rgba(36, 53, 45, .12); border-radius: 10px; font: 400 .875rem var(--font); color: var(--ink); background: #fafaf8; }
    input:focus, select:focus, textarea:focus { border-color: #c17817; outline: 2px solid rgba(193, 120, 23, .18); outline-offset: 0; }
    .dark .stock-module input:not([type=checkbox]):not([type=radio]), .dark .stock-module select, .dark .stock-module textarea { border-color: rgba(255,255,255,.12); color: #fafaf8; background: #152a20; }
    .dark .stock-module input::placeholder, .dark .stock-module textarea::placeholder { color: rgba(250,250,248,.42); }
    textarea { min-height: 88px; resize: vertical; }
    .hint { font-size: 0.83rem; color: var(--ink-soft); }
    .error { font-size: 0.84rem; color: var(--brick); font-weight: 600; }
    .check { display: flex; gap: 10px; align-items: center; font-weight: 500; }
    .check[hidden] { display: none; }
    .check input { width: 18px; height: 18px; accent-color: #1f3d2e; }
    .form-foot { display: flex; gap: 10px; margin-top: 24px; padding-top: 18px; border-top: 1px solid var(--rule); flex-wrap: wrap; }

    .filters { display: flex; gap: 12px; align-items: flex-end; flex-wrap: wrap; margin-bottom: 20px; padding: 16px; border: 1px solid rgba(31,61,46,.1); border-radius: 16px; background: #fff; box-shadow: 0 1px 3px rgba(31,61,46,.06); }
    .dark .filters { border-color: rgba(255,255,255,.1); background: #1b2923; box-shadow: none; }
    .filters .field { min-width: 170px; flex: 1; }
    .filters .field label { font-size: 0.8rem; color: var(--ink-soft); }

    .choices { display: grid; grid-template-columns: repeat(auto-fit, minmax(190px, 1fr)); gap: 10px; border: 0; padding: 0; margin: 0 0 22px; }
    .choices legend { font-weight: 600; font-size: 0.9rem; margin-bottom: 8px; padding: 0; }
    .choice { position: relative; display: block; border: 1px solid rgba(36,53,45,.14); border-radius: 12px; padding: 12px 14px 12px 40px; cursor: pointer; background: #fff; font-weight: 400; }
    .dark .choice { border-color: rgba(255,255,255,.13); background: #152a20; color: #fafaf8; }
    .choice input { position: absolute; left: 14px; top: 15px; accent-color: var(--pine); width: 16px; height: 16px; }
    .choice b { display: block; }
    .choice small { color: var(--ink-soft); font-size: 0.84rem; }
    .choice:has(input:checked) { border-color: var(--pine); background: var(--pine-wash); box-shadow: inset 0 0 0 1px var(--pine); }
    .dark .choice:has(input:checked) { border-color: #c17817; background: rgba(193,120,23,.14); box-shadow: inset 0 0 0 1px #c17817; }
    .choice:has(input:disabled) { opacity: .5; cursor: not-allowed; }

    .notice { border-radius: 12px; padding: 14px 16px; margin-bottom: 20px; border: 1px solid; font-size: .875rem; }
    .notice-success { background: rgba(45,106,79,.08); border-color: rgba(45,106,79,.2); color: #24543f; }
    .notice-error { background: rgba(178,58,58,.08); border-color: rgba(178,58,58,.2); color: #9b3030; }
    .notice-warning { background: rgba(193,120,23,.09); border-color: rgba(193,120,23,.22); color: #80500f; }
    .dark .notice-success { background: rgba(45,106,79,.2); color: #bfe5cf; border-color: rgba(167,216,188,.18); }
    .dark .notice-error { background: rgba(178,58,58,.2); color: #ffc1c1; border-color: rgba(255,193,193,.16); }
    .dark .notice-warning { background: rgba(193,120,23,.18); color: #f2d4a2; border-color: rgba(242,212,162,.18); }
    .notice-warning .btn { margin-top: 10px; }
    .notice ul { margin: 6px 0 0; padding-left: 18px; }
    .notice-title { font-weight: 700; }

    .empty { padding: 40px 24px; text-align: center; color: var(--ink-soft); }
    .dark .empty { color: #b7c2ba; }
    .empty .btn { margin-top: 12px; }
    .pager { margin-top: 16px; }
    .pagination { display: flex; gap: 4px; list-style: none; padding: 0; margin: 0; flex-wrap: wrap; }
    .pagination li > a, .pagination li > span { display: block; min-width: 36px; text-align: center; padding: 7px 11px; border: 1px solid rgba(31,61,46,.12); border-radius: 10px; background: #fff; color: var(--ink); text-decoration: none; font-variant-numeric: tabular-nums; }
    .dark .pagination li > a, .dark .pagination li > span { border-color: rgba(255,255,255,.12); background: #1b2923; color: #e2e8e3; }
    .pagination .active > span { background: #c17817; border-color: #c17817; color: #fff; }
    .pagination .disabled > span { color: #9aaaa5; }

    .steps { list-style: none; margin: 0; padding: 0; display: grid; gap: 12px; }
    .steps > li { display: flex; gap: 14px; align-items: flex-start; padding: 14px 16px; border: 1px solid rgba(31,61,46,.1); border-radius: 14px; background: #fff; }
    .dark .steps > li { border-color: rgba(255,255,255,.1); background: #152a20; }
    .steps > li.is-alert { border-color: rgba(178,58,58,.24); background: rgba(178,58,58,.07); }
    .steps p { margin: 2px 0 0; max-width: 95ch; }
    .step-n { flex: none; display: grid; place-items: center; width: 28px; height: 28px; border-radius: 50%; background: #1f3d2e; color: #fff; font-weight: 800; }
    .steps > li.is-alert .step-n { background: #b23a3a; }
    .step-t { font-weight: 700; margin: 0 !important; }
    .events { margin: 4px 0 0; padding-left: 18px; }
    .week { display: flex; gap: 10px; margin: 8px 0 4px; flex-wrap: wrap; }
    .week-day { display: grid; justify-items: center; gap: 2px; font-size: .8rem; min-width: 48px; }
    .week-day b { font-variant-numeric: tabular-nums; }
    .week-bar { display: flex; align-items: flex-end; width: 26px; height: 60px; background: rgba(36,53,45,.1); border-radius: 6px; overflow: hidden; }
    .dark .week-bar { background: rgba(255,255,255,.1); }
    .week-bar i { display: block; width: 100%; background: var(--ink-soft); }
    .week-bar i.is-up { background: var(--pine); }
    .week-bar i.is-down { background: var(--amber); }

    @media (max-width: 720px) {
        .panel { padding: 16px; }
        .panel > .table-wrap { margin: 0 -16px -16px; }
        .filters .field { min-width: min(100%, 170px); }
    }

    @media (max-width: 720px) {
        .page-head { margin-bottom: 18px; }
    }
    @media (prefers-reduced-motion: reduce) { * { transition: none !important; animation: none !important; } }
</style>

<x-dashboard-layout>
    <x-slot name="header">
        <div class="flex w-full flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="min-w-0">
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-amber-warm">@yield('eyebrow', 'Stock workspace')</p>
                <h1 class="mt-1 font-fraunces text-3xl font-bold text-ink dark:text-white">@yield('title', 'Stocks')</h1>
                @hasSection('description')
                    <p class="mt-2 max-w-3xl text-sm leading-6 text-ink/55 dark:text-white/55">@yield('description')</p>
                @endif
            </div>
            @hasSection('page-action')
                <div class="flex shrink-0 flex-wrap items-center gap-2">@yield('page-action')</div>
            @endif
        </div>
    </x-slot>

    <div class="stock-module space-y-6">
        {{-- Les messages session('success') / session('error') sont déjà affichés par x-dashboard-layout --}}
        @if ($errors->any())
            <div class="notice notice-error" role="alert">
                <p class="notice-title">Corrigez les points suivants :</p>
                <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif

        @yield('content')
    </div>
</x-dashboard-layout>
