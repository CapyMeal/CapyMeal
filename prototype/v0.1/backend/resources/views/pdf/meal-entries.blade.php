<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #3F3F46; font-size: 12px; }
        h1 { color: #40384A; margin-bottom: 6px; }
        .range { color: #6B5F77; margin-bottom: 16px; }
        .card {
            border: 1px solid #E6E2EA;
            border-radius: 10px;
            padding: 12px;
            margin-bottom: 12px;
            page-break-inside: avoid;
        }
        .date { font-weight: 700; color: #A24E78; margin-bottom: 8px; }
        .line { margin: 3px 0; line-height: 1.4; }
        .empty { color: #8E8598; font-style: italic; }
    </style>
</head>
<body>
    <h1>CapyMeal — Diario de comidas</h1>
    <div class="range">
        @if ($from || $to)
            Rango:
            {{ $from ?? 'inicio' }} → {{ $to ?? 'hoy' }}
        @else
            Rango: todos los registros
        @endif
    </div>

    @forelse ($entries as $entry)
        <div class="card">
            <div class="date">{{ \Carbon\Carbon::parse($entry->date)->format('d/m/Y') }}</div>
            <div class="line"><strong>☀️ Desayuno:</strong> {{ $entry->breakfast ?: 'No registrado' }}</div>
            <div class="line"><strong>🍝 Almuerzo:</strong> {{ $entry->lunch ?: 'No registrado' }}</div>
            <div class="line"><strong>🧁 Merienda:</strong> {{ $entry->snack ?: 'No registrado' }}</div>
            <div class="line"><strong>🌙 Cena:</strong> {{ $entry->dinner ?: 'No registrado' }}</div>
            @if ($entry->notes)
                <div class="line"><strong>📝 Recuerdo:</strong> {{ $entry->notes }}</div>
            @endif
        </div>
    @empty
        <p class="empty">No hay registros para ese rango.</p>
    @endforelse
</body>
</html>
