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
    .title-status {
        display: inline-block;
        font-size: 8pt;
        font-weight: bold;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        color: #6b7280;
        background: #f3f4f6;
        border-radius: 3px;
        padding: 3px 8px;
        margin-top: 4px;
    }
    .title-rule { height: 1px; background: #d1d5db; margin-top: 16px; }

    /* ── Stammdaten ── */
    .meta-grid { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
    .meta-grid td { padding: 5px 0; font-size: 9.5pt; vertical-align: top; }
    .meta-label {
        width: 130px;
        font-size: 7pt;
        font-weight: bold;
        text-transform: uppercase;
        letter-spacing: 0.1em;
        color: #9ca3af;
    }
    .meta-value { color: #1f2937; }
    .description-text { font-size: 9.5pt; color: #4b5563; margin-top: 10px; white-space: pre-wrap; }

    /* ── Abschnitte ── */
    .section { padding: 20px 0; page-break-inside: avoid; }
    .section + .section { border-top: 1px solid #f3f4f6; }
    .section-label {
        font-size: 7pt;
        font-weight: bold;
        text-transform: uppercase;
        letter-spacing: 0.12em;
        color: #9ca3af;
        margin-bottom: 12px;
    }

    /* ── Produkttabelle ── */
    table.product-table { width: 100%; border-collapse: collapse; }
    table.product-table th {
        text-align: left;
        font-size: 7pt;
        font-weight: bold;
        text-transform: uppercase;
        letter-spacing: 0.1em;
        color: #9ca3af;
        border-bottom: 1px solid #d1d5db;
        padding: 5px 6px;
    }
    table.product-table td {
        padding: 7px 6px;
        border-bottom: 1px solid #f3f4f6;
        font-size: 9.5pt;
        vertical-align: top;
    }
    table.product-table td.col-qty { width: 50px; text-align: center; color: #6b7280; }
    table.product-table td.col-price { width: 90px; text-align: right; }
    table.product-table td.col-total { width: 90px; text-align: right; font-weight: bold; }
    table.product-table .no-price { color: #d97706; font-style: italic; }
    table.product-table tfoot td {
        border-bottom: none;
        border-top: 1px solid #d1d5db;
        font-weight: bold;
        padding-top: 10px;
    }

    .estimate-hint { font-size: 8pt; color: #9ca3af; margin-top: 8px; }
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

{{-- ── Titel + Status ── --}}
<div class="title-block">
    <div class="title-name">{{ $project->name }}</div>
    <span class="title-status">{{ $project->statusLabel() }}</span>
    @if($project->isLocked())<span class="title-status">Fixiert</span>@endif
    <div class="title-rule"></div>
</div>

{{-- ── Stammdaten ── --}}
<div class="section" style="padding-top: 0;">
    <table class="meta-grid">
        <tr>
            <td class="meta-label">Kategorie</td>
            <td class="meta-value">{{ $project->categoryLabel() }}</td>
        </tr>
        <tr>
            <td class="meta-label">Auftraggeber</td>
            <td class="meta-value">{{ $project->client ?? '–' }}</td>
        </tr>
        <tr>
            <td class="meta-label">Verantwortlich</td>
            <td class="meta-value">{{ $project->assigneeLabel() }}</td>
        </tr>
        <tr>
            <td class="meta-label">Ausschreibungsjahr</td>
            <td class="meta-value">{{ $project->tender_year ?? '–' }}</td>
        </tr>
        <tr>
            <td class="meta-label">Fällig</td>
            <td class="meta-value">{{ $project->due_date ? $project->due_date->format('d.m.Y') : '–' }}</td>
        </tr>
    </table>
    @if($project->description)
        <div class="description-text">{{ $project->description }}</div>
    @endif
</div>

{{-- ── Produktliste ── --}}
<div class="section">
    <div class="section-label">Produkte</div>

    @if(count($lines))
    <table class="product-table">
        <thead>
            <tr>
                <th>Bezeichnung</th>
                <th class="col-qty">Menge</th>
                <th class="col-price">Einzelpreis</th>
                <th class="col-total">Gesamt</th>
            </tr>
        </thead>
        <tbody>
            @foreach($lines as $line)
            <tr>
                <td>{{ $line['name'] }}</td>
                <td class="col-qty">{{ $line['count'] }}×</td>
                @if($line['has_price'])
                    <td class="col-price">{{ number_format($line['unit_price'], 2, ',', '.') }} €</td>
                    <td class="col-total">{{ number_format($line['line_total'], 2, ',', '.') }} €</td>
                @else
                    <td class="col-price no-price" colspan="2">Kein Preis hinterlegt</td>
                @endif
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3">Gesamtsumme (geschätzt)</td>
                <td class="col-total">{{ number_format($estimate['total'], 2, ',', '.') }} €</td>
            </tr>
        </tfoot>
    </table>
    @if($estimate['fixed'])
        <p class="estimate-hint">Preise zum Zeitpunkt der Fixierung eingefroren.</p>
    @endif
    @if($estimate['missing_count'] > 0)
        <p class="estimate-hint">{{ $estimate['missing_count'] }} von {{ $estimate['positions_count'] }} Position(en) ohne Preis, nicht in der Summe enthalten.</p>
    @endif
    @else
        <p style="font-size: 9pt; color: #9ca3af; font-style: italic;">Diesem Projekt sind noch keine Produkte zugeordnet.</p>
    @endif
</div>

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
