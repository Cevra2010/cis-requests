<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<style>
    body, div, p, img { margin: 0; padding: 0; box-sizing: border-box; }

    @page { margin: 25mm 20mm 22mm 20mm; }

    body {
        font-family: DejaVu Sans, sans-serif;
        color: #1f2937;
    }

    .doc-header { padding-bottom: 14px; margin-bottom: 30px; }
    .doc-header-inner { display: flex; align-items: center; gap: 16px; margin-bottom: 10px; }
    .doc-header-logo { max-height: 48px; max-width: 120px; }
    .doc-header-org { font-size: 13pt; font-weight: bold; color: #111827; }
    .doc-header-sub { font-size: 9pt; color: #6b7280; margin-top: 2px; }
    .accent-line { height: 2px; border-radius: 1px; }

    .label-box {
        text-align: center;
        padding: 40px 20px;
        border: 2px solid #d1d5db;
        border-radius: 12px;
    }
    .label-qr { width: 260px; height: 260px; }
    .label-path { font-size: 9pt; color: #9ca3af; margin-top: 20px; }
    .label-name { font-size: 20pt; font-weight: bold; color: #111827; margin-top: 4px; }
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

<div class="label-box">
    <img src="{{ $qrDataUri }}" class="label-qr" alt="QR-Code">
    <div class="label-path">{{ $lagerort->path() }}</div>
    <div class="label-name">{{ $lagerort->name }}</div>
</div>

</body>
</html>
