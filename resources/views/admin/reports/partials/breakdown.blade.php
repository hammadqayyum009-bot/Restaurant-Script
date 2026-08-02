@if ($rows->isEmpty())
    <div class="a-empty">Nothing to show for this range.</div>
@else
    <div class="a-stack">
        @foreach ($rows as $row)
            @php
                $count = (int) $row->orders;
                $pct = $total > 0 ? round($count / $total * 100) : 0;
            @endphp
            <div>
                <div style="display:flex; justify-content:space-between; gap:12px; font-size:0.88rem; margin-bottom:5px;">
                    <span>{{ ucwords(str_replace('_', ' ', $row->{$key})) }}</span>
                    <span class="a-muted" style="font-variant-numeric:tabular-nums;">
                        {{ $count }} &middot; {{ config('site.currency') }} {{ number_format((float) $row->total, 2) }}
                    </span>
                </div>
                <div class="meter" role="img" aria-label="{{ $pct }} percent">
                    <div class="meter-fill" style="width: {{ max($pct, 1) }}%"></div>
                </div>
            </div>
        @endforeach
    </div>
@endif
