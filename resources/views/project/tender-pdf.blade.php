<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<style>
    body, div, p, table, th, td, h1, h2, h3, h4, ul, ol, li, img {
        margin: 0; padding: 0; box-sizing: border-box;
    }

    @page {
        margin: 25mm 20mm 22mm 20mm;

        @bottom-right {
            content: "Seite " counter(page) " / " counter(pages);
            font-family: DejaVu Sans, sans-serif;
            font-size: 8pt;
            color: #9ca3af;
        }
    }

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

    /* Materialliste */
    .content-block { padding: 24px 0; }
    .block-section-label {
        font-size: 7pt;
        font-weight: bold;
        text-transform: uppercase;
        letter-spacing: 0.12em;
        margin-bottom: 16px;
    }
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

    /* Materialliste (Tabelle) */
    table.material-table { width: 100%; border-collapse: collapse; }
    table.material-table th {
        text-align: left;
        font-size: 7pt;
        font-weight: bold;
        text-transform: uppercase;
        letter-spacing: 0.1em;
        color: #9ca3af;
        border-bottom: 1px solid #d1d5db;
        padding: 5px 6px;
    }
    table.material-table td {
        padding: 7px 6px;
        border-bottom: 1px solid #f3f4f6;
        font-size: 9.5pt;
        vertical-align: top;
    }
    table.material-table td.col-pos { width: 28px; color: #9ca3af; }
    table.material-table td.col-qty { width: 50px; text-align: center; color: #6b7280; }
    table.material-table td.col-name { width: 26%; }
    table.material-table tr.subproduct-row td.col-name { padding-left: 18px; color: #d97706; font-size: 8.5pt; }
    table.material-table tr.subproduct-row td { border-bottom: 1px dashed #f3f4f6; }
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
        $showLabel = $block->config['show_label'] ?? false;
    @endphp
    <div class="content-block">

        @if($showLabel)
        <div class="block-section-label" style="color: #d97706">Materialliste</div>
        @endif

        @if(isset($block->resolvedItems) && $block->resolvedItems->count())
        <table class="material-table">
            <thead>
                <tr>
                    <th class="col-pos">Pos.</th>
                    <th class="col-qty">Menge</th>
                    <th class="col-name">Bezeichnung</th>
                    <th>Beschreibung</th>
                </tr>
            </thead>
            <tbody>
                @foreach($block->resolvedItems as $item)
                <tr>
                    <td class="col-pos">{{ $loop->iteration }}</td>
                    <td class="col-qty">{{ $item->product_count > 1 ? $item->product_count . '×' : '1×' }}</td>
                    <td class="col-name">
                        {{ $item->name }}
                        @if($item->note)<div class="item-note">{{ $item->note }}</div>@endif
                    </td>
                    <td>
                        @if($item->text)
                            {{ $item->text }}
                        @else
                            <span class="item-empty">Kein Beschreibungstext vorhanden.</span>
                        @endif
                    </td>
                </tr>
                @foreach($item->children as $child)
                <tr class="subproduct-row">
                    <td class="col-pos"></td>
                    <td class="col-qty"></td>
                    <td class="col-name">{{ $child->name }}</td>
                    <td>
                        @if($child->text)
                            {{ $child->text }}
                        @else
                            <span class="item-empty">Kein Beschreibungstext vorhanden.</span>
                        @endif
                    </td>
                </tr>
                @endforeach
                @endforeach
            </tbody>
        </table>
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
