@extends('layout.html')

@section('body')
<div class="min-h-full bg-gray-50">
    <header class="bg-white border-b border-gray-200 px-4 py-3 flex items-center gap-2.5">
        <div class="flex items-center justify-center w-7 h-7 rounded-lg bg-primary-600 shrink-0">
            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
        </div>
        <p class="text-sm font-semibold text-gray-900">CIS Requests</p>
    </header>

    @if(session('success'))
        <div class="px-4 pt-4">
            <div class="cis-success">
                <i class="fa fa-check-circle"></i>
                {{ session('success') }}
            </div>
        </div>
    @endif

    <main class="max-w-lg mx-auto px-4 py-5">
        @yield('content')
    </main>
</div>
@endsection
