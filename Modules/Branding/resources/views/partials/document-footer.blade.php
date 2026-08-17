@php
    $branding = \Modules\Branding\Models\BrandingSetting::current();
@endphp

@if($branding->footer_left || $branding->footer_right)
<div class="pt-4 mt-2">
    <div class="h-0.5 rounded-full mb-3" style="background-color: {{ $branding->accent_color }}"></div>
    <div class="flex items-center justify-between text-[11px] text-gray-400">
        <span>{{ $branding->footer_left }}</span>
        <span>{{ $branding->footer_right }}</span>
    </div>
</div>
@endif
