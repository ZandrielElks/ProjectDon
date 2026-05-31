<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title') - BagStack ERP</title>
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
</head>

<body>
    <div class="app-container">
        <aside class="sidebar">
            <div class="sidebar-logo">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                    stroke-linecap="round" stroke-linejoin="round">
                    <path
                        d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z">
                    </path>
                    <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
                    <line x1="12" y1="22.08" x2="12" y2="12"></line>
                </svg>
                <span>BagStack</span>
            </div>
            <nav class="sidebar-nav">
                <a href="{{ route('dashboard') }}"
                    class="sidebar-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="3" width="7" height="7"></rect>
                        <rect x="14" y="3" width="7" height="7"></rect>
                        <rect x="14" y="14" width="7" height="7"></rect>
                        <rect x="3" y="14" width="7" height="7"></rect>
                    </svg>
                    <span>Dashboard</span>
                </a>
                <a href="{{ route('transactions.index') }}"
                    class="sidebar-link {{ request()->routeIs('transactions.*') ? 'active' : '' }}">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
                    </svg>
                    <span>Transaksi</span>
                </a>
                <a href="{{ route('categories.index') }}"
                    class="sidebar-link {{ request()->routeIs('categories.*') ? 'active' : '' }}">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path>
                    </svg>
                    <span>Kategori</span>
                </a>
                <a href="{{ route('bills.index') }}"
                    class="sidebar-link {{ request()->routeIs('bills.*') ? 'active' : '' }}">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="4" width="18" height="16" rx="2"></rect>
                        <line x1="3" y1="10" x2="21" y2="10"></line>
                        <line x1="7" y1="15" x2="7.01" y2="15"></line>
                        <line x1="11" y1="15" x2="13" y2="15"></line>
                    </svg>
                    <span>Tagihan</span>
                </a>
                <a href="{{ route('simulator.index') }}"
                    class="sidebar-link {{ request()->routeIs('simulator.*') ? 'active' : '' }}">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="4" width="18" height="16" rx="2"></rect>
                        <line x1="3" y1="10" x2="21" y2="10"></line>
                        <line x1="7" y1="15" x2="7.01" y2="15"></line>
                        <line x1="11" y1="15" x2="13" y2="15"></line>
                    </svg>
                    <span>Simulator</span>
                </a>
            </nav>
        </aside>

        <main class="main-content">
            @if(session('success'))
                <div class="card" style="margin-bottom: 1rem; background-color: #D1FAE5; color: #065F46; border: none;">
                    {{ session('success') }}
                </div>
            @endif

            @if($errors->any())
                <div class="card" style="margin-bottom: 1rem; background-color: #FEE2E2; color: #991B1B; border: none;">
                    <ul>
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @yield('content')
        </main>
    </div>

    @yield('scripts')
</body>

</html>