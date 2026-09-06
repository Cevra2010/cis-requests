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

    /* ── Branding (identisch zu tender-pdf.blade.php) ── */
    .doc-header { padding-bottom: 14px; margin-bottom: 10px; }
    .doc-header-inner { display: flex; align-items: center; gap: 16px; margin-bottom: 10px; }
    .doc-header-logo { max-height: 48px; max-width: 120px; }
    .doc-header-org { font-size: 13pt; font-weight: bold; color: #111827; }
    .doc-header-sub { font-size: 9pt; color: #6b7280; margin-top: 2px; }
    .accent-line { height: 2px; border-radius: 1px; }
    .doc-footer { padding-top: 12px; margin-top: 10px; }
    .doc-footer-inner { display: flex; justify-content: space-between; font-size: 8pt; color: #9ca3af; }

    /* ── Titel ── */
    .title-block { padding: 24px 0 20px; }
    .title-name { font-size: 16pt; font-weight: bold; color: #111827; margin-bottom: 4px; }
    .title-sub { font-size: 9.5pt; color: #6b7280; }
    .title-rule { height: 1px; background: #d1d5db; margin-top: 16px; }

    /* ── Abschnitt je Quelle ── */
    .section { padding: 20px 0; page-break-inside: avoid; }
    .section + .section { border-top: 1px solid #f3f4f6; }
    .section-label {
        font-size: 12pt;
        font-weight: bold;
        color: #111827;
        margin-bottom: 12px;
    }
    .section-hint { font-size: 8.5pt; color: #9ca3af; margin-top: -8px; margin-bottom: 12px; }

    table.item-table { width: 100%; border-collapse: collapse; }
    table.item-table th {
        text-align: left;
        font-size: 7pt;
        font-weight: bold;
        text-transform: uppercase;
        letter-spacing: 0.1em;
        color: #9ca3af;
        border-bottom: 1px solid #d1d5db;
        padding: 5px 6px;
    }
    table.item-table td {
        padding: 7px 6px;
        border-bottom: 1px solid #f3f4f6;
        font-size: 9.5pt;
    }
    table.item-table td.col-qty { width: 80px; text-align: center; color: #6b7280; }
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
            @if($branding->header_line1)<div class="doc-header-org">{{ $branding->header_line1 }}</div>@endif
            @if($branding->header_line2)<div class="doc-header-sub">{{ $branding->header_line2 }}</div>@endif
        </div>
        @endif
    </div>
    <div class="accent-line" style="background-color: {{ $branding->accent_color }};"></div>
</div>
@endif

{{-- ── Titel ── --}}
<div class="title-block">
    <div class="title-name">Materialanforderung</div>
    <div class="title-sub">{{ $project->name }}</div>
    <div class="title-rule"></div>
</div>

@forelse($groups as $group)
<div class="section">
    <div class="section-label">{{ $group['source']?->name ?? 'Unbekannte Quelle' }}</div>
    <p class="section-hint">Bitte folgendes Material bereitstellen:</p>
    <table class="item-table">
        <thead>
            <tr>
                <th>Bezeichnung</th>
                <th class="col-qty">Menge</th>
            </tr>
        </thead>
        <tbody>
            @foreach($group['items'] as $item)
            <tr>
                <td>{{ $item['name'] }}</td>
                <td class="col-qty">{{ $item['count'] }}×</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@empty
<p style="font-size: 9pt; color: #9ca3af; font-style: italic;">
    Keine Positionen mit fester, nicht-ausschreibungsrelevanter Quelle in diesem Projekt.
</p>
@endforelse

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
