<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Sistem Pembayaran')</title>
    @include('layouts.favicon')

    <link href="{{ asset('css/fonts.css') }}?v=20261014-1" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="{{ asset('css/table-sort.css') }}?v=20261021-2" rel="stylesheet">
    @stack('page-styles')
    <link href="{{ asset('css/permata-system.css') }}?v=20260913-11" rel="stylesheet">
    <link href="{{ asset('css/filter-groups.css') }}?v=20260913-1" rel="stylesheet">
    <link href="{{ asset('css/transaction-revisions.css') }}?v=20261014-1" rel="stylesheet">
    <link href="{{ asset('css/report-layout.css') }}?v=20261014-3" rel="stylesheet">
    <link href="{{ asset('css/menu-density.css') }}?v=20261014-1" rel="stylesheet">
    <link href="{{ asset('css/admin-layout-fixes.css') }}?v=20261021-1" rel="stylesheet">
    <link href="{{ asset('css/account-pages.css') }}?v=20261021-1" rel="stylesheet">
    <link href="{{ asset('css/table-pagination.css') }}?v=20261021-3" rel="stylesheet">
    <link href="{{ asset('css/compact-filters.css') }}?v=20261021-5" rel="stylesheet">
    <script src="{{ asset('js/table-sort.js') }}?v=20261021-6" defer></script>
    <script src="{{ asset('js/table-pagination.js') }}?v=20261021-3" defer></script>
    <script src="{{ asset('js/server-pagination.js') }}?v=20261021-2" defer></script>
    @if(request()->routeIs('tagihan.index.grouped'))
        <link href="{{ asset('css/billing-first-paint.css') }}?v=20261021-1" rel="stylesheet">
        <script>
            (() => {
                document.documentElement.dataset.billingBoot = 'pending';
                const reveal = () => {
                    document.documentElement.dataset.billingBoot = 'ready';
                    document.querySelector('.billing-table-wrap')?.removeAttribute('aria-busy');
                };
                document.addEventListener('DOMContentLoaded', () => requestAnimationFrame(reveal), { once: true });
                window.addEventListener('pageshow', event => { if (event.persisted) reveal(); });
                window.setTimeout(reveal, 8000);
            })();
        </script>
    @endif
</head>
<body
    data-list-context="{{ request()->attributes->get('preserved_list_context', '') }}"
    data-list-page="{{ request()->attributes->get('is_preserved_list_page', false) ? 'true' : 'false' }}"
>
    @isset($header)
        {{ $header }}
    @endisset

    <main>
        @yield('content')
        {{ $slot ?? '' }}
    </main>

    @include('partials.confirm-dialog')

    <script src="{{ asset('js/permata-system.js') }}?v=20261014-4"></script>
    <script src="{{ asset('js/preserve-list-position.js') }}?v=20260813-1"></script>
    <script src="{{ asset('js/filter-groups.js') }}?v=20260913-3"></script>
    <script src="{{ asset('js/compact-filters.js') }}?v=20261021-3"></script>
    @if(request()->routeIs('tagihan.index.grouped'))
        <script src="{{ asset('js/native-billing-toolbar.js') }}?v=20261021-2"></script>
        <script src="{{ asset('js/billing-live-search.js') }}?v=20261021-2"></script>
    @endif
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const heading = document.querySelector('[data-page-title], .page-title, .dashboard-title, main h1, main h2');
            if (heading && !document.title.trim()) document.title = heading.textContent.trim();
        });
    </script>
</body>
</html>
