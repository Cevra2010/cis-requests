<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<style>
    body, div, p, img { margin: 0; padding: 0; box-sizing: border-box; }

    @page { margin: 20mm 20mm 15mm 20mm; }

    body {
        font-family: DejaVu Sans, sans-serif;
        color: #1f2937;
    }

    .doc-header { padding-bottom: 14px; margin-bottom: 20px; }
    .doc-header-inner { display: flex; align-items: center; gap: 16px; margin-bottom: 10px; }
    .doc-header-logo { max-height: 48px; max-width: 120px; }
    .doc-header-org { font-size: 13pt; font-weight: bold; color: #111827; }
    .doc-header-sub { font-size: 9pt; color: #6b7280; margin-top: 2px; }
    .accent-line { height: 2px; border-radius: 1px; }

    .hint { font-size: 8pt; color: #9ca3af; margin-bottom: 8mm; }

    .label-box {
        text-align: center;
        border: 1.5px dashed #d1d5db;
        border-radius: 8px;
        margin-bottom: 8mm;
    }
    .label-path { color: #9ca3af; }
    .label-name { font-weight: bold; color: #111827; }

    .label-box-lg { padding: 10mm; }
    .label-box-lg .label-qr { width: 65mm; height: 65mm; }
    .label-box-lg .label-path { font-size: 9pt; margin-top: 6mm; }
    .label-box-lg .label-name { font-size: 20pt; margin-top: 2mm; }

    .label-box-md { padding: 6mm; }
    .label-box-md .label-qr { width: 40mm; height: 40mm; }
    .label-box-md .label-path { font-size: 8pt; margin-top: 4mm; }
    .label-box-md .label-name { font-size: 14pt; margin-top: 1mm; }

    .label-box-sm { padding: 2mm; max-height: 40mm; }
    .label-box-sm .label-qr { width: 22mm; height: 22mm; }
    .label-box-sm .label-name { font-size: 9pt; margin-top: 1.5mm; }
</style>
</head>
<body>

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

<p class="hint">Passende Größe ausschneiden und aufkleben.</p>

<div class="label-box label-box-lg">
    <img src="{{ $qrDataUri }}" class="label-qr" alt="QR-Code">
    <div class="label-path">{{ $lagerort->path() }}</div>
    <div class="label-name">{{ $lagerort->name }}</div>
</div>

<div class="label-box label-box-md">
    <img src="{{ $qrDataUri }}" class="label-qr" alt="QR-Code">
    <div class="label-path">{{ $lagerort->path() }}</div>
    <div class="label-name">{{ $lagerort->name }}</div>
</div>

<div class="label-box label-box-sm">
    <img src="{{ $qrDataUri }}" class="label-qr" alt="QR-Code">
    <div class="label-name">{{ $lagerort->name }}</div>
</div>

</body>
</html>
