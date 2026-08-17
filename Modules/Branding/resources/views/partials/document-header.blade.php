@php
    $branding = isset($preview)
        ? app(\Modules\Branding\Models\BrandingSetting::class)::current()
        : \Modules\Branding\Models\BrandingSetting::current();
    $logoUrl  = $branding->logoUrl();
@endphp

@if($logoUrl || $branding->header_line1)
<div class="pb-4 mb-2">
    <div class="flex items-center gap-5">

        @if($logoUrl)
        <img src="{{ $logoUrl }}"
             alt="Logo"
             class="h-12 w-auto object-contain shrink-0">
        @endif

        @if($branding->header_line1 || $branding->header_line2)
        <div>
            @if($branding->header_line1)
            <p class="text-base font-bold text-gray-900 leading-tight">{{ $branding->header_line1 }}</p>
            @endif
            @if($branding->header_line2)
            <p class="text-sm text-gray-500 mt-0.5">{{ $branding->header_line2 }}</p>
            @endif
        </div>
        @endif

    </div>

    <div class="mt-3 h-0.5 rounded-full" style="background-color: {{ $branding->accent_color }}"></div>
</div>
@endif
