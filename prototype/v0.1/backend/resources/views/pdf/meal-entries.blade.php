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
            padding: 28px;
        }

        /* ── Portada ── */
        .cover {
            text-align: center;
            padding: 32px 0 28px;
            margin-bottom: 28px;
            border-bottom: 2px solid #F4B6D7;
        }

        .cover__chef {
            width: 90px;
            margin-bottom: 10px;
        }

        .cover__title {
            font-size: 26px;
            font-weight: 700;
            color: #3F3F46;
            letter-spacing: -0.5px;
            margin-bottom: 4px;
        }

        .cover__tagline {
            font-size: 11px;
            color: #6B6B72;
            margin-bottom: 12px;
        }

        .cover__badge {
            display: inline-block;
            background: #F4B6D7;
            color: #3F3F46;
            border-radius: 999px;
            padding: 4px 14px;
            font-size: 10px;
            font-weight: 700;
        }

        /* ── Tarjeta de día ── */
        .day-card {
            background: #FFFFFF;
            border: 1.5px solid #EDE9F2;
            border-radius: 14px;
            margin-bottom: 16px;
            overflow: hidden;
            page-break-inside: avoid;
        }

        .day-card__header {
            background: linear-gradient(90deg, #F4B6D7 0%, #DCCCF4 100%);
            padding: 10px 16px;
        }

        .day-card__date {
            font-size: 13px;
            font-weight: 700;
            color: #3F3F46;
            text-transform: capitalize;
        }

        .day-card__body {
            padding: 12px 16px;
        }

        /* ── Fila de comida ── */
        .meal-row {
            display: table;
            width: 100%;
            padding: 7px 0;
            border-bottom: 1px solid #F0EBF5;
        }

        .meal-row:last-child {
            border-bottom: none;
        }

        .meal-row__icon-cell {
            display: table-cell;
            width: 32px;
            vertical-align: middle;
        }

        .meal-row__icon {
            width: 26px;
            height: 26px;
        }

        .meal-row__content {
            display: table-cell;
            vertical-align: middle;
            padding-left: 8px;
        }

        .meal-row__label {
            font-size: 9px;
            font-weight: 700;
            color: #A98274;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 2px;
        }

        .meal-row__value {
            font-size: 11px;
            color: #3F3F46;
            line-height: 1.4;
        }

        .meal-row__empty {
            font-size: 10px;
            color: #B0AABF;
            font-style: italic;
        }

        /* ── Recuerdo del día ── */
        .notes-row {
            margin-top: 10px;
            background: #FDF6FB;
            border: 1px solid #F4B6D7;
            border-radius: 8px;
            padding: 8px 12px;
        }

        .notes-row__label {
            font-size: 9px;
            font-weight: 700;
            color: #A98274;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 3px;
        }

        .notes-row__value {
            font-size: 11px;
            color: #3F3F46;
            line-height: 1.5;
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
            margin-top: 28px;
            padding-top: 14px;
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
                            <div class="meal-row__value">{{ $entry->breakfast }}</div>
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
                            <div class="meal-row__value">{{ $entry->lunch }}</div>
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
                            <div class="meal-row__value">{{ $entry->snack }}</div>
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
                            <div class="meal-row__value">{{ $entry->dinner }}</div>
                        @else
                            <div class="meal-row__empty">No registrado</div>
                        @endif
                    </div>
                </div>

                @if ($entry->notes)
                    <div class="notes-row">
                        <div class="notes-row__label">📝 Recuerdo del día</div>
                        <div class="notes-row__value">{{ $entry->notes }}</div>
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

