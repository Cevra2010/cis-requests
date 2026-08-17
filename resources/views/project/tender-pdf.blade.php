<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }

    body {
        font-family: DejaVu Sans, sans-serif;
        font-size: 10pt;
        color: #1f2937;
        line-height: 1.6;
    }

    /* ── Branding ── */
    .doc-header {
        padding-bottom: 14px;
        margin-bottom: 10px;
    }
    .doc-header-inner {
        display: flex;
        align-items: center;
        gap: 16px;
        margin-bottom: 10px;
    }
    .doc-header-logo {
        max-height: 48px;
        max-width: 120px;
    }
    .doc-header-org {
        font-size: 13pt;
        font-weight: bold;
        color: #111827;
    }
    .doc-header-sub {
        font-size: 9pt;
        color: #6b7280;
        margin-top: 2px;
    }
    .accent-line {
        height: 2px;
        border-radius: 1px;
    }

    .doc-footer {
        padding-top: 12px;
        margin-top: 10px;
    }
    .doc-footer-inner {
        display: flex;
        justify-content: space-between;
        font-size: 8pt;
        color: #9ca3af;
    }

    /* ── Blocks ── */
    .block { page-break-inside: avoid; }
    .block + .block { border-top: 1px solid #f3f4f6; }

    /* Heading */
    .heading-block { padding: 32px 0 20px; }
    .heading-title {
        font-size: 16pt;
        font-weight: bold;
        color: #111827;
        margin-bottom: 8px;
    }
    .heading-rule { height: 1px; background: #d1d5db; }

    /* Properties / Products */
    .content-block { padding: 24px 0; }
    .block-section-label {
        font-size: 7pt;
        font-weight: bold;
        text-transform: uppercase;
        letter-spacing: 0.12em;
        margin-bottom: 16px;
    }
    .block-section-rule {
        display: inline-block;
        width: 100%;
        height: 1px;
        background: #f3f4f6;
        vertical-align: middle;
    }

    /* Item */
    .item { margin-bottom: 22px; page-break-inside: avoid; }
    .item-name {
        font-size: 7.5pt;
        font-weight: bold;
        text-transform: uppercase;
        letter-spacing: 0.1em;
        margin-bottom: 4px;
    }
    .item-name-properties { color: #059669; }
    .item-name-products   { color: #d97706; }
    .item-text {
        font-size: 10pt;
        color: #374151;
        line-height: 1.65;
    }
    .item-note {
        font-size: 8.5pt;
        color: #9ca3af;
        font-style: italic;
    }
    .item-empty {
        font-size: 9pt;
        color: #9ca3af;
        font-style: italic;
    }

    /* Sub-products */
    .subproducts {
        margin-top: 12px;
        margin-left: 14px;
        padding-left: 10px;
        border-left: 2px solid #fde68a;
    }
    .subproduct { margin-bottom: 14px; page-break-inside: avoid; }
    .subproduct-name {
        font-size: 7pt;
        font-weight: bold;
        text-transform: uppercase;
        letter-spacing: 0.1em;
        color: #d97706;
        margin-bottom: 3px;
    }
</style>
</head>
<body>

{{-- ── Branding Header ── --}}
@if($branding && ($branding->logoUrl() || $branding->header_line1))
<div class="doc-header">
    <div class="doc-header-inner">
        @if($branding->logoUrl())
        <img src="{{ public_path('storage/' . ltrim($branding->logo_path, '/')) }}"
             class="doc-header-logo" alt="Logo">
        @endif
        @if($branding->header_line1 || $branding->header_line2)
        <div>
            @if($branding->header_line1)
            <div class="doc-header-org">{{ $branding->header_line1 }}</div>
            @endif
            @if($branding->header_line2)
            <div class="doc-header-sub">{{ $branding->header_line2 }}</div>
            @endif
        </div>
        @endif
    </div>
    <div class="accent-line" style="background-color: {{ $branding->accent_color }};"></div>
</div>
@endif

{{-- ── Blocks ── --}}
@foreach($resolvedBlocks as $block)
<div class="block">

    @if($block->type === 'heading')
    <div class="heading-block">
        <div class="heading-title">{{ $block->config['text'] ?? '' }}</div>
        <div class="heading-rule"></div>
    </div>

    @elseif($block->type === 'text')
    <div class="content-block" style="padding: 20px 0;">
        @if($block->config['text'] ?? '')
            <div class="item-text" style="white-space: pre-wrap;">{{ $block->config['text'] }}</div>
        @endif
    </div>

    @elseif($block->type === 'space')
    <div style="height: {{ (int)($block->config['height'] ?? 40) }}px;"></div>

    @else
    @php
        $isProperties = $block->type === 'properties';
        $showLabel    = $block->config['show_label'] ?? false;
        $labelColor   = $isProperties ? '#059669' : '#d97706';
        $nameClass    = $isProperties ? 'item-name-properties' : 'item-name-products';
    @endphp
    <div class="content-block">

        @if($showLabel)
        <div class="block-section-label" style="color: {{ $labelColor }}">
            {{ $isProperties ? 'Eigenschaften' : 'Produkte' }}
        </div>
        @endif

        @if(isset($block->resolvedItems))
        @foreach($block->resolvedItems as $item)
        <div class="item">
            <div class="item-name {{ $nameClass }}">
                @if(!$isProperties && $item->product_count > 1){{ $item->product_count }}× @endif
                {{ $item->name }}
                @if(!$isProperties && $item->note)
                    <span class="item-note"> — {{ $item->note }}</span>
                @endif
            </div>
            @if($item->text)
                <div class="item-text">{{ $item->text }}</div>
            @else
                <div class="item-empty">Kein Beschreibungstext vorhanden.</div>
            @endif

            {{-- Unterprodukte --}}
            @if(!$isProperties && $item->children->count())
            <div class="subproducts">
                @foreach($item->children as $child)
                <div class="subproduct">
                    <div class="subproduct-name">{{ $child->name }}</div>
                    @if($child->text)
                        <div class="item-text">{{ $child->text }}</div>
                    @else
                        <div class="item-empty">Kein Beschreibungstext vorhanden.</div>
                    @endif
                </div>
                @endforeach
            </div>
            @endif
        </div>
        @endforeach
        @endif

    </div>
    @endif

</div>
@endforeach

{{-- ── Branding Footer ── --}}
@if($branding && ($branding->footer_left || $branding->footer_right))
<div class="doc-footer">
    <div class="accent-line" style="background-color: {{ $branding->accent_color }}; margin-bottom: 8px;"></div>
    <div class="doc-footer-inner">
        <span>{{ $branding->footer_left }}</span>
        <span>{{ $branding->footer_right }}</span>
    </div>
</div>
@endif

</body>
</html>
