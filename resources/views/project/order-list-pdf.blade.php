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
        line-height: 1.5;
    }

    .doc-header { padding-bottom: 14px; margin-bottom: 16px; }
    .doc-header-inner { display: flex; align-items: center; gap: 16px; margin-bottom: 10px; }
    .doc-header-logo { max-height: 48px; max-width: 120px; }
    .doc-header-org { font-size: 13pt; font-weight: bold; color: #111827; }
    .doc-header-sub { font-size: 9pt; color: #6b7280; margin-top: 2px; }
    .accent-line { height: 2px; border-radius: 1px; background: #111827; }

    .doc-title { font-size: 15pt; font-weight: bold; color: #111827; margin-bottom: 4px; }
    .doc-subtitle { font-size: 10pt; color: #6b7280; margin-bottom: 20px; }

    table.order-table { width: 100%; border-collapse: collapse; margin-top: 8px; }
    table.order-table th {
        text-align: left;
        font-size: 7.5pt;
        font-weight: bold;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: #6b7280;
        border-bottom: 1px solid #d1d5db;
        padding: 6px 8px;
    }
    table.order-table td {
        padding: 7px 8px;
        border-bottom: 1px solid #f3f4f6;
        font-size: 9.5pt;
        vertical-align: top;
    }
    table.order-table td.num { text-align: right; white-space: nowrap; }
    table.order-table td.note { color: #9ca3af; font-size: 8.5pt; }
    .col-pos   { width: 30px; }
    .col-qty   { width: 60px; }
    .col-price { width: 90px; }
    .col-sum   { width: 100px; }

    .total-row td {
        border-top: 2px solid #111827;
        border-bottom: none;
        font-weight: bold;
        font-size: 10.5pt;
        padding-top: 10px;
    }

    .below-min-hint {
        margin-top: 14px;
        padding: 8px 12px;
        background: #fef3c7;
        border: 1px solid #fde68a;
        border-radius: 6px;
        font-size: 8.5pt;
        color: #92400e;
    }

    .doc-footer { padding-top: 12px; margin-top: 24px; }
    .doc-footer-inner { display: flex; justify-content: space-between; font-size: 8pt; color: #9ca3af; }
</style>
</head>
<body>

@if($branding && ($branding->logoUrl() || $branding->header_line1))
<div class="doc-header">
    <div class="doc-header-inner">
        @if($branding->logoUrl())
        <img src="{{ public_path('storage/' . ltrim($branding->logo_path, '/')) }}" class="doc-header-logo" alt="Logo">
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

<div class="doc-title">Bestellliste – {{ $offer->source->name }}</div>
<div class="doc-subtitle">
    Projekt: {{ $project->name }}
    @if($offer->reference) &middot; Referenz: {{ $offer->reference }} @endif
    @if($offer->submitted_at) &middot; Angebot vom {{ $offer->submitted_at->format('d.m.Y') }} @endif
</div>

<table class="order-table">
    <thead>
        <tr>
            <th class="col-pos">Pos.</th>
            <th>Bezeichnung</th>
            <th class="col-qty">Menge</th>
            <th class="col-price">Einzelpreis</th>
            <th class="col-sum">Summe</th>
        </tr>
    </thead>
    <tbody>
        @forelse($rows as $i => $row)
        <tr>
            <td>{{ $i + 1 }}</td>
            <td>
                {{ $row->name }}
                @if($row->note)<div class="note">{{ $row->note }}</div>@endif
            </td>
            <td class="num">{{ $row->qty }}</td>
            <td class="num">{{ number_format($row->price, 2, ',', '.') }} €</td>
            <td class="num">{{ number_format($row->sum, 2, ',', '.') }} €</td>
        </tr>
        @empty
        <tr><td colspan="5" style="color:#9ca3af;">Keine Positionen zugeordnet.</td></tr>
        @endforelse
    </tbody>
    <tfoot>
        <tr class="total-row">
            <td colspan="4">Gesamtsumme</td>
            <td class="num">{{ number_format($total, 2, ',', '.') }} €</td>
        </tr>
    </tfoot>
</table>

@if($offer->min_value_ignored)
<div class="below-min-hint">
    Hinweis: Der Mindestbestellwert wurde für diesen Anbieter bewusst unterschritten (manuell akzeptiert).
</div>
@endif

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
