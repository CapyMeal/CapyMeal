<?php

namespace App\Support;

class MealDiaryPdfRenderer
{
    /**
     * Incrusta un icono local como data URI en base64. DomPDF 3 no puede
     * leer rutas de archivo locales (chroot) de forma confiable, y pedirse
     * a si mismo la URL publica via HTTP se traba porque `php artisan
     * serve` atiende una sola request a la vez (deadlock). Leer el
     * archivo directo evita ambos problemas.
     */
    public static function iconDataUri(string $filename): string
    {
        static $cache = [];

        if (! isset($cache[$filename])) {
            $path = public_path('images/'.$filename);
            $cache[$filename] = 'data:image/png;base64,'.base64_encode(file_get_contents($path));
        }

        return $cache[$filename];
    }

    /**
     * Texto libre del usuario (comidas/notas) para mostrar en el PDF:
     * escapa cualquier HTML antes de convertir emojis a imagenes, para
     * que un usuario no pueda inyectar markup (ni pedirle al servidor
     * que traiga una URL remota) a traves de su propio diario.
     */
    public static function renderText(string $text): string
    {
        return self::renderEmoji(e($text));
    }

    /**
     * Los PNG de Twemoji se bajan una sola vez y quedan en disco: pegarle a
     * la CDN por cada emoji en cada PDF fue lo que hizo lenta/pesada la
     * exportacion la vez anterior que se probo esto. Con el archivo ya
     * cacheado, las siguientes exportaciones no vuelven a tocar la red.
     */
    private static function emojiDataUri(string $filename): ?string
    {
        static $memCache = [];

        if (array_key_exists($filename, $memCache)) {
            return $memCache[$filename];
        }

        $cachePath = storage_path('app/emoji-cache/'.$filename.'.png');

        if (! is_file($cachePath)) {
            $url = 'https://cdn.jsdelivr.net/gh/jdecked/twemoji@latest/assets/72x72/'.$filename.'.png';
            $contents = @file_get_contents($url);

            if ($contents === false) {
                return $memCache[$filename] = null;
            }

            if (! is_dir(dirname($cachePath))) {
                mkdir(dirname($cachePath), 0755, true);
            }

            file_put_contents($cachePath, $contents);
        }

        return $memCache[$filename] = 'data:image/png;base64,'.base64_encode(file_get_contents($cachePath));
    }

    private static function renderEmoji(string $text): string
    {
        return preg_replace_callback(
            '/(?:[\x{1F000}-\x{1FFFF}]|[\x{2600}-\x{27BF}]|\x{2B50}|\x{2B55})(?:\x{200D}(?:[\x{1F000}-\x{1FFFF}]|[\x{2600}-\x{27BF}]))*\x{FE0F}?/u',
            function ($m) {
                $chars = preg_split('//u', $m[0], -1, PREG_SPLIT_NO_EMPTY);
                $codepoints = array_values(array_filter(
                    array_map(fn ($c) => mb_ord($c), $chars),
                    fn ($cp) => $cp !== 0xFE0F
                ));
                $filename = implode('-', array_map(fn ($cp) => strtolower(dechex($cp)), $codepoints));
                $dataUri = self::emojiDataUri($filename);

                // Si la descarga falla (CDN caida, sin red), se deja el
                // emoji como texto plano en vez de mostrar un icono roto.
                if ($dataUri === null) {
                    return $m[0];
                }

                return '<img src="'.$dataUri.'" width="13" height="13" style="vertical-align:middle;margin:0 1px;">';
            },
            $text
        );
    }
}
