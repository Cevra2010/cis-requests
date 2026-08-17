@extends('layout.html')

@section('body')

{{-- ── Sidebar ── --}}
<aside class="cis-sidebar">

    {{-- Branding --}}
    <div class="cis-sidebar-logo">
        <div class="flex items-center justify-center w-8 h-8 rounded-lg bg-primary-600 shrink-0">
            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
        </div>
        <div>
            <p class="text-sm font-semibold text-white leading-tight">CIS Requests</p>
            <p class="text-[10px] text-gray-500 leading-tight mt-0.5">Ausschreibungen</p>
        </div>
    </div>

    {{-- Navigation --}}
    <nav class="cis-sidebar-nav">
        <p class="px-2 pt-2 pb-1 text-[10px] font-semibold tracking-widest text-gray-600 uppercase">Hauptmenü</p>
        <x-cis-menu slug="main"/>
    </nav>

    {{-- User footer --}}
    <div class="px-2 py-3 border-t border-gray-800 shrink-0">
        <a href="{{ route('dashboard.self') }}"
           class="flex items-center gap-2.5 px-2 py-2 rounded-lg text-gray-400 hover:bg-gray-800 hover:text-white transition-colors">
            <img src="{{ auth()->user()?->avatarUrl() }}"
                 alt="{{ auth()->user()?->name() }}"
                 class="w-7 h-7 rounded-full object-cover shrink-0">
            <div class="flex-1 min-w-0">
                <p class="text-xs font-medium text-gray-300 truncate">{{ auth()->user()?->name() }}</p>
                <p class="text-[10px] text-gray-600 truncate">Mein Konto</p>
            </div>
            <i class="fa fa-cog text-xs text-gray-600 shrink-0"></i>
        </a>

        <form method="POST" action="{{ route('auth.logout') }}" class="mt-1">
            @csrf
            <button type="submit"
                    class="w-full flex items-center gap-2 px-2 py-1.5 rounded-lg text-xs text-gray-600 hover:bg-gray-800 hover:text-red-400 transition-colors">
                <i class="fa fa-sign-out-alt w-4 text-center"></i>
                Abmelden
            </button>
        </form>
    </div>
</aside>

{{-- ── Main area ── --}}
<div class="cis-page">

    {{-- Top bar --}}
    <header class="cis-topbar">
        <div class="flex items-center gap-2 min-w-0">
            <h1 class="text-sm font-semibold text-gray-900 truncate">@yield('title', 'Dashboard')</h1>
        </div>
        <div class="flex items-center gap-2 shrink-0">
            @yield('header_actions')
        </div>
    </header>

    {{-- Flash messages --}}
    @if(session('success'))
        <div class="px-6 pt-4">
            <div class="cis-success">
                <i class="fa fa-check-circle"></i>
                {{ session('success') }}
            </div>
        </div>
    @endif
    @if(session('error'))
        <div class="px-6 pt-4">
            <div class="cis-error">
                <i class="fa fa-exclamation-circle"></i>
                {{ session('error') }}
            </div>
        </div>
    @endif

    {{-- Page content --}}
    <main class="cis-content">
        @yield('content')
    </main>
</div>

@endsection
