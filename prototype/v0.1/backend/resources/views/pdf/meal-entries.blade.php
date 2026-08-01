{{-- Convierte emoji Unicode a imágenes Twemoji para DomPDF --}}
@php
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

function cleanMealValue(?string $text): string {
    $value = trim((string) $text);
    return preg_replace('/^\s*(desayuno|almuerzo|merienda|cena)\s*[:\-]?\s*/iu', '', $value) ?? $value;
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
            background: #F5F5F7;
            color: #3F3F46;
            font-size: 11px;
            padding: 14px 16px;
        }

        /* ── Portada ── */
        .cover {
            text-align: center;
            padding: 14px 0 10px;
            margin-bottom: 10px;
            border-bottom: 2px solid #F4B6D7;
        }

        .cover__chef {
            width: 52px;
            margin-bottom: 4px;
        }

        .cover__title {
            font-size: 19px;
            font-weight: 700;
            color: #3F3F46;
            letter-spacing: -0.5px;
            margin-bottom: 3px;
        }

        .cover__tagline {
            font-size: 9px;
            color: #6B6B72;
            margin-bottom: 5px;
        }

        .cover__badge {
            display: inline-block;
            background: #F4B6D7;
            color: #3F3F46;
            border-radius: 999px;
            padding: 2px 10px;
            font-size: 9px;
            font-weight: 700;
        }

        /* ── Tarjeta de día ── */
        .day-card {
            background: #FFFFFF;
            border: 1.5px solid #EDE9F2;
            border-radius: 10px;
            margin-bottom: 6px;
            overflow: hidden;
            page-break-inside: avoid;
        }

        .day-card__header {
            background: linear-gradient(90deg, #F4B6D7 0%, #DCCCF4 100%);
            padding: 4px 10px;
        }

        .day-card__date {
            font-size: 11px;
            font-weight: 700;
            color: #3F3F46;
            text-transform: capitalize;
        }

        .day-card__body {
            padding: 4px 10px;
        }

        /* ── Fila de comida ── */
        .meal-row {
            display: table;
            width: 100%;
            padding: 2px 0;
            border-bottom: 1px solid #F0EBF5;
        }

        .meal-row:last-child {
            border-bottom: none;
        }

        .meal-row__icon-cell {
            display: table-cell;
            width: 20px;
            vertical-align: top;
            padding-top: 1px;
        }

        .meal-row__icon {
            width: 14px;
            height: 14px;
        }

        .meal-row__content {
            display: table-cell;
            vertical-align: top;
            padding-left: 5px;
        }

        .meal-row__label {
            font-size: 8px;
            font-weight: 700;
            color: #A98274;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 0;
        }

        .meal-row__value {
            font-size: 10px;
            color: #3F3F46;
            line-height: 1.2;
        }

        .meal-row__empty {
            font-size: 9px;
            color: #B0AABF;
            font-style: italic;
        }

        /* ── Recuerdo del día ── */
        .notes-row {
            margin-top: 4px;
            background: #FDF6FB;
            border: 1px solid #F4B6D7;
            border-radius: 6px;
            padding: 3px 8px;
        }

        .notes-row__label {
            font-size: 8px;
            font-weight: 700;
            color: #A98274;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 1px;
        }

        .notes-row__value {
            font-size: 10px;
            color: #3F3F46;
            line-height: 1.2;
        }

        /* ── Sin resultados ── */
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #B0AABF;
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
            border-top: 1px solid #EDE9F2;
            font-size: 9px;
            color: #B0AABF;
        }
    </style>
</head>
<body>

    {{-- Portada --}}
    <div class="cover">
        <img class="cover__chef" src="{{ public_path('images/Chef.png') }}" alt="Capi">
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
                        <img class="meal-row__icon" src="{{ public_path('images/desayuno.png') }}" alt="Desayuno">
                    </div>
                    <div class="meal-row__content">
                        <div class="meal-row__label">Desayuno</div>
                        @if ($entry->breakfast)
                            <div class="meal-row__value">{!! renderEmoji(cleanMealValue($entry->breakfast)) !!}</div>
                        @else
                            <div class="meal-row__empty">No registrado</div>
                        @endif
                    </div>
                </div>

                <div class="meal-row">
                    <div class="meal-row__icon-cell">
                        <img class="meal-row__icon" src="{{ public_path('images/almuerzo.png') }}" alt="Almuerzo">
                    </div>
                    <div class="meal-row__content">
                        <div class="meal-row__label">Almuerzo</div>
                        @if ($entry->lunch)
                            <div class="meal-row__value">{!! renderEmoji(cleanMealValue($entry->lunch)) !!}</div>
                        @else
                            <div class="meal-row__empty">No registrado</div>
                        @endif
                    </div>
                </div>

                <div class="meal-row">
                    <div class="meal-row__icon-cell">
                        <img class="meal-row__icon" src="{{ public_path('images/merienda.png') }}" alt="Merienda">
                    </div>
                    <div class="meal-row__content">
                        <div class="meal-row__label">Merienda</div>
                        @if ($entry->snack)
                            <div class="meal-row__value">{!! renderEmoji(cleanMealValue($entry->snack)) !!}</div>
                        @else
                            <div class="meal-row__empty">No registrado</div>
                        @endif
                    </div>
                </div>

                <div class="meal-row">
                    <div class="meal-row__icon-cell">
                        <img class="meal-row__icon" src="{{ public_path('images/cena.png') }}" alt="Cena">
                    </div>
                    <div class="meal-row__content">
                        <div class="meal-row__label">Cena</div>
                        @if ($entry->dinner)
                            <div class="meal-row__value">{!! renderEmoji(cleanMealValue($entry->dinner)) !!}</div>
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
            <img class="empty-state__chef" src="{{ public_path('images/Chef.png') }}" alt="Capi">
            <p>No hay registros para ese rango.</p>
        </div>
    @endforelse

    <div class="footer">
        CapyMeal · Generado el {{ now()->translatedFormat('j \d\e F \d\e Y') }}
    </div>

</body>
</html>
