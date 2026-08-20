@php
    $chart = \App\Services\PriceHistoryService::buildChartData($product);
@endphp

@if(! $chart['has_data'])
    <div class="text-center py-10 text-gray-400">
        <i class="fa fa-chart-line text-2xl mb-2 block"></i>
        <p class="text-sm">Noch nicht genug Preisdaten für einen Verlauf.</p>
        <p class="text-xs mt-0.5">Sobald mindestens zwei Preise erfasst sind, erscheint hier die Entwicklung.</p>
    </div>
@else
    @php
        $palette = ['#2563eb', '#16a34a', '#ea580c', '#7c3aed', '#db2777', '#0891b2', '#ca8a04'];

        $padLeft = 46; $padRight = 12; $padTop = 12; $padBottom = 22;
        $width = 640; $height = 220;
        $innerW = $width - $padLeft - $padRight;
        $innerH = $height - $padTop - $padBottom;

        $minAmount = $chart['min'];
        $maxAmount = $chart['max'];
        $amountSpan = max($maxAmount - $minAmount, 0.01);
        $minAmount -= $amountSpan * 0.1;
        $maxAmount += $amountSpan * 0.1;
        $amountSpan = $maxAmount - $minAmount;

        $minTime = $chart['min_date']->timestamp;
        $maxTime = $chart['max_date']->timestamp;
        $timeSpan = max($maxTime - $minTime, 1);

        $toXY = function ($point) use ($minTime, $timeSpan, $minAmount, $amountSpan, $padLeft, $padRight, $padTop, $padBottom, $width, $height) {
            $innerW = $width - $padLeft - $padRight;
            $innerH = $height - $padTop - $padBottom;
            $x = $padLeft + (($point['date']->timestamp - $minTime) / $timeSpan) * $innerW;
            $y = $padTop + $innerH - ((($point['amount'] - $minAmount) / $amountSpan) * $innerH);
            return [round($x, 1), round($y, 1)];
        };

        $buildPolyline = fn ($points) => implode(' ', array_map(fn ($p) => implode(',', $toXY($p)), $points));

        $sourceSeries = collect($chart['sources'])->values();
    @endphp

    <svg viewBox="0 0 {{ $width }} {{ $height }}" class="w-full h-auto" style="max-height: 260px;">
        {{-- Gitterlinien --}}
        @foreach([0, 0.25, 0.5, 0.75, 1] as $fraction)
            @php $gy = $padTop + $innerH * (1 - $fraction); @endphp
            <line x1="{{ $padLeft }}" y1="{{ $gy }}" x2="{{ $width - $padRight }}" y2="{{ $gy }}" stroke="#f3f4f6" stroke-width="1" />
            <text x="{{ $padLeft - 6 }}" y="{{ $gy + 3 }}" text-anchor="end" font-size="8" fill="#9ca3af">
                {{ number_format($minAmount + $amountSpan * $fraction, 0, ',', '.') }}
            </text>
        @endforeach

        {{-- Lieferanten-Linien --}}
        @foreach($sourceSeries as $i => $points)
            <polyline points="{{ $buildPolyline($points) }}" fill="none"
                      stroke="{{ $palette[$i % count($palette)] }}" stroke-width="1.5" opacity="0.75" />
            @foreach($points as $p)
                @php [$x, $y] = $toXY($p); @endphp
                <circle cx="{{ $x }}" cy="{{ $y }}" r="2" fill="{{ $palette[$i % count($palette)] }}" opacity="0.75" />
            @endforeach
        @endforeach

        {{-- Durchschnitts-Linie (im Vordergrund, betont) --}}
        <polyline points="{{ $buildPolyline($chart['average']) }}" fill="none"
                  stroke="#111827" stroke-width="2.5" />
        @foreach($chart['average'] as $p)
            @php [$x, $y] = $toXY($p); @endphp
            <circle cx="{{ $x }}" cy="{{ $y }}" r="2.5" fill="#111827" />
        @endforeach

        {{-- X-Achse: Datumsbereich --}}
        <text x="{{ $padLeft }}" y="{{ $height - 6 }}" font-size="8" fill="#9ca3af">{{ $chart['min_date']->format('d.m.Y') }}</text>
        <text x="{{ $width - $padRight }}" y="{{ $height - 6 }}" text-anchor="end" font-size="8" fill="#9ca3af">{{ $chart['max_date']->format('d.m.Y') }}</text>
    </svg>

    {{-- Legende --}}
    <div class="flex flex-wrap items-center gap-x-4 gap-y-1.5 mt-3 text-xs">
        <span class="flex items-center gap-1.5 font-medium text-gray-700">
            <span class="w-2.5 h-0.5 rounded-full inline-block" style="background: #111827;"></span>
            Ø Durchschnitt
        </span>
        @foreach($sourceSeries as $i => $points)
            <span class="flex items-center gap-1.5 text-gray-500">
                <span class="w-2.5 h-0.5 rounded-full inline-block" style="background: {{ $palette[$i % count($palette)] }};"></span>
                {{ array_keys($chart['sources'])[$i] }}
            </span>
        @endforeach
    </div>
@endif
