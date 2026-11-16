<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Arma la secuencia de días que muestra el PDF: los días registrados y,
 * entre ellos, los tramos sin registro.
 *
 * Antes el PDF listaba solo los días con algo anotado, y para quien lo lee
 * en una consulta (una médica, una nutricionista) un hueco se veía igual
 * que un registro continuo. Ahora un día sin anotar es un dato visible,
 * dicho sin juicio. Los días seguidos sin registro se juntan en un solo
 * renglón ("del 14 al 20 de noviembre") para que una semana sin anotar no
 * ocupe siete.
 *
 * El rango va de `from` (o el primer día registrado) a `to` (o el último
 * día registrado): sin fecha de fin elegida no se inventa un tramo vacío
 * hasta hoy.
 */
final class DiaryTimeline
{
    /**
     * @param  Collection<int, object{date: mixed}>  $entries  ordenadas por fecha ascendente
     * @return list<array{type: 'entry', entry: object}|array{type: 'gap', from: CarbonImmutable, to: CarbonImmutable}>
     */
    public static function build(Collection $entries, ?string $from, ?string $to): array
    {
        if ($entries->isEmpty()) {
            return [];
        }

        $day = fn ($value) => CarbonImmutable::parse($value)->startOfDay();

        $cursor = $from ? $day($from) : $day($entries->first()->date);
        $end = $to ? $day($to) : $day($entries->last()->date);

        $items = [];

        foreach ($entries as $entry) {
            $date = $day($entry->date);

            if ($date->greaterThan($cursor)) {
                $items[] = self::gap($cursor, $date->subDay());
            }

            $items[] = ['type' => 'entry', 'entry' => $entry];
            $cursor = $date->addDay();
        }

        if ($end->greaterThanOrEqualTo($cursor)) {
            $items[] = self::gap($cursor, $end);
        }

        return $items;
    }

    /**
     * @return array{type: 'gap', from: CarbonImmutable, to: CarbonImmutable}
     */
    private static function gap(CarbonImmutable $from, CarbonImmutable $to): array
    {
        return ['type' => 'gap', 'from' => $from, 'to' => $to];
    }
}
