{{-- Incrusta los iconos locales como base64: DomPDF 3 no puede leer
     rutas de archivo locales (chroot) de forma confiable, y pedirse a
     si mismo la URL publica via HTTP se traba porque `php artisan
     serve` atiende una sola request a la vez (deadlock). Leer el
     archivo directo evita ambos problemas. Se cachea en memoria para
     no releer el mismo archivo por cada fila/dia. --}}
@php
function iconDataUri(string $filename): string {
    static $cache = [];

    if (!isset($cache[$filename])) {
        $path = public_path('images/' . $filename);
        $cache[$filename] = 'data:image/png;base64,' . base64_encode(file_get_contents($path));
    }

    return $cache[$filename];
}

function renderEmoji(string $text): string {
    return preg_replace_callback(
        '/(?:[\x{1F000}-\x{1FFFF}]|[\x{2600}-\x{27BF}]|\x{2B50}|\x{2B55})(?:\x{200D}(?:[\x{1F000}-\x{1FFFF}]|[\x{2600}-\x{27BF}]))*\x{FE0F}?/u',
        function ($m) {
            $chars = preg_split('//u', $m[0], -1, PREG_SPLIT_NO_EMPTY);
            $codepoints = array_values(array_filter(
                array_map(fn($c) => mb_ord($c), $chars),
                fn($cp) => $cp !== 0xFE0F
            ));
            $filename = implode('-', array_map(fn($cp) => strtolower(dechex($cp)), $codepoints));
            return '<img src="https://cdn.jsdelivr.net/npm/@twemoji/api@latest/dist/assets/72x72/' . $filename . '.png" width="13" height="13" style="vertical-align:middle;margin:0 1px;">';
        },
        $text
    );
}
@endphp

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: DejaVu Sans, sans-serif;
            background: #F2EAE0;
            color: #362B22;
            font-size: 11px;
            padding: 14px 18px;
        }

        /* ── Portada ── */
        .cover {
            text-align: center;
            padding: 14px 0 10px;
            margin-bottom: 10px;
            border-bottom: 2px solid #96684A;
        }

        .cover__chef {
            width: 64px;
            margin-bottom: 6px;
        }

        .cover__title {
            font-size: 22px;
            font-weight: 700;
            color: #362B22;
            letter-spacing: -0.5px;
            margin-bottom: 3px;
        }

        .cover__tagline {
            font-size: 10px;
            color: #6E6156;
            margin-bottom: 8px;
        }

        .cover__badge {
            display: inline-block;
            background: #96684A;
            color: #FBF7F1;
            border-radius: 999px;
            padding: 3px 12px;
            font-size: 10px;
            font-weight: 700;
        }

        /* ── Tarjeta de día ── */
        .day-card {
            background: #FFFFFF;
            border: 1.5px solid #DED2C0;
            border-radius: 10px;
            margin-bottom: 5px;
            overflow: hidden;
            page-break-inside: avoid;
        }

        .day-card__header {
            /* DomPDF no renderiza linear-gradient() de forma confiable
               (queda transparente) -- color solido en su lugar. */
            background: #96684A;
            padding: 3px 10px;
        }

        .day-card__date {
            font-size: 12px;
            font-weight: 700;
            color: #FBF7F1;
            text-transform: capitalize;
        }

        .day-card__body {
            padding: 3px 10px;
        }

        /* ── Fila de comida ── */
        .meal-row {
            display: table;
            width: 100%;
            padding: 2px 0;
            border-bottom: 1px solid #EDE3D3;
        }

        .meal-row:last-child {
            border-bottom: none;
        }

        .meal-row__icon-cell {
            display: table-cell;
            width: 22px;
            vertical-align: middle;
        }

        .meal-row__icon {
            width: 16px;
            height: 16px;
        }

        .meal-row__content {
            display: table-cell;
            vertical-align: middle;
            padding-left: 6px;
        }

        .meal-row__label {
            font-size: 8px;
            font-weight: 700;
            color: #96684A;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .meal-row__value {
            font-size: 11px;
            color: #362B22;
            line-height: 1.3;
        }

        .meal-row__empty {
            font-size: 10px;
            color: #A79C8E;
            font-style: italic;
        }

        /* ── Recuerdo del día ── */
        .notes-row {
            margin-top: 3px;
            background: #F7F0E4;
            border: 1px solid #96684A;
            border-radius: 6px;
            padding: 3px 10px;
        }

        .notes-row__label {
            font-size: 8px;
            font-weight: 700;
            color: #96684A;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 2px;
        }

        .notes-row__value {
            font-size: 11px;
            color: #362B22;
            line-height: 1.4;
        }

        /* ── Sin resultados ── */
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #A79C8E;
        }

        .empty-state__chef {
            width: 64px;
            margin-bottom: 10px;
            opacity: 0.5;
        }

        /* ── Footer ── */
        .footer {
            text-align: center;
            margin-top: 16px;
            padding-top: 10px;
            border-top: 1px solid #DED2C0;
            font-size: 9px;
            color: #A79C8E;
        }
    </style>
</head>
<body>

    {{-- Portada --}}
    <div class="cover">
        <img class="cover__chef" src="{{ iconDataUri('Chef.png') }}" alt="Capi">
        <div class="cover__title">CapyMeal</div>
        <div class="cover__tagline">Las comidas pasan. Los recuerdos quedan.</div>
        <span class="cover__badge">
            @if ($from || $to)
                {{ $from ?? '...' }} → {{ $to ?? 'hoy' }}
            @else
                Todos los registros
            @endif
        </span>
    </div>

    {{-- Entradas --}}
    @forelse ($entries as $entry)
        <div class="day-card">

            <div class="day-card__header">
                <div class="day-card__date">
                    {{ \Carbon\Carbon::parse($entry->date)->translatedFormat('l, j \d\e F \d\e Y') }}
                </div>
            </div>

            <div class="day-card__body">

                <div class="meal-row">
                    <div class="meal-row__icon-cell">
                        <img class="meal-row__icon" src="{{ iconDataUri('desayuno.png') }}" alt="Desayuno">
                    </div>
                    <div class="meal-row__content">
                        <div class="meal-row__label">Desayuno</div>
                        @if ($entry->breakfast)
                            <div class="meal-row__value">{!! renderEmoji($entry->breakfast) !!}</div>
                        @else
                            <div class="meal-row__empty">No registrado</div>
                        @endif
                    </div>
                </div>

                <div class="meal-row">
                    <div class="meal-row__icon-cell">
                        <img class="meal-row__icon" src="{{ iconDataUri('almuerzo.png') }}" alt="Almuerzo">
                    </div>
                    <div class="meal-row__content">
                        <div class="meal-row__label">Almuerzo</div>
                        @if ($entry->lunch)
                            <div class="meal-row__value">{!! renderEmoji($entry->lunch) !!}</div>
                        @else
                            <div class="meal-row__empty">No registrado</div>
                        @endif
                    </div>
                </div>

                <div class="meal-row">
                    <div class="meal-row__icon-cell">
                        <img class="meal-row__icon" src="{{ iconDataUri('merienda.png') }}" alt="Merienda">
                    </div>
                    <div class="meal-row__content">
                        <div class="meal-row__label">Merienda</div>
                        @if ($entry->snack)
                            <div class="meal-row__value">{!! renderEmoji($entry->snack) !!}</div>
                        @else
                            <div class="meal-row__empty">No registrado</div>
                        @endif
                    </div>
                </div>

                <div class="meal-row">
                    <div class="meal-row__icon-cell">
                        <img class="meal-row__icon" src="{{ iconDataUri('cena.png') }}" alt="Cena">
                    </div>
                    <div class="meal-row__content">
                        <div class="meal-row__label">Cena</div>
                        @if ($entry->dinner)
                            <div class="meal-row__value">{!! renderEmoji($entry->dinner) !!}</div>
                        @else
                            <div class="meal-row__empty">No registrado</div>
                        @endif
                    </div>
                </div>

                @if ($entry->notes)
                    <div class="notes-row">
                        <div class="notes-row__label">📝 Recuerdo del día</div>
                        <div class="notes-row__value">{!! renderEmoji($entry->notes) !!}</div>
                    </div>
                @endif

            </div>
        </div>
    @empty
        <div class="empty-state">
            <img class="empty-state__chef" src="{{ iconDataUri('Chef.png') }}" alt="Capi">
            <p>No hay registros para ese rango.</p>
        </div>
    @endforelse

    <div class="footer">
        CapyMeal · Generado el {{ now()->translatedFormat('j \d\e F \d\e Y') }}
    </div>

</body>
</html>

