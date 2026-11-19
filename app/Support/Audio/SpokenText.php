<?php

namespace App\Support\Audio;

use Illuminate\Support\Str;

final class SpokenText
{
    public function prepare(string $markdown): string
    {
        $text = preg_replace('/^\h*(`{3,}|~{3,})[^\n]*\n.*?(?:^\h*\1[^\n]*$|\z)/ms', '', $markdown);
        $text = preg_replace('/^\h*\[[^\]]+\]:.*$/m', '', $text);
        $text = preg_replace('/^\h*(?:[-*]\h*)?(?:fuentes?|referencias?|sources?|references?|rutas?|archivos?)\h*:[^\n]*$/imu', '', $text);
        $text = preg_replace('/!?\[([^\]]*)\]\([^)]*\)/u', '$1', $text);
        $text = preg_replace_callback('/`([^`]+)`/u', static function (array $match): string {
            $symbol = $match[1];

            return mb_strlen($symbol) <= 40 && preg_match('/\A[\pL_][\pL\pN_]*\z/u', $symbol) ? $symbol : '';
        }, $text);
        $text = preg_replace('~https?://\S+|(?:[\w.-]+/)+[\w.{}:@-]+(?::\d+)?~u', '', $text);
        $text = preg_replace('/^[\s|:*-]*\|.*$/m', '', $text);
        $text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = Str::squish(preg_replace('/[#*_>]+/u', '', $text));
        $text = preg_replace('/\h+([,.;:!?])/u', '$1', $text);
        $limit = (int) config('audio.max_speech_characters');

        return mb_strlen($text) > $limit
            ? mb_substr($text, 0, $limit - 70).'… La respuesta completa está en pantalla.'
            : $text;
    }
}
