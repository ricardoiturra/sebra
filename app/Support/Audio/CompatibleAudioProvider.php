<?php

namespace App\Support\Audio;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;

class CompatibleAudioProvider implements SpeechToText, TextToSpeech
{
    public function transcribe(UploadedFile $audio): string
    {
        $http = $this->client('stt');
        $stream = fopen($audio->getRealPath(), 'rb');
        try {
            $response = $http->attach('file', $stream, 'recording.'.$audio->guessExtension())->post(config('audio.stt.url'), [
                'model' => config('audio.stt.model'), 'language' => 'es', 'response_format' => 'json',
            ]);
        } catch (ConnectionException) {
            abort(502, 'No se pudo conectar con el proveedor STT. Podés escribir tu pregunta.');
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }
        abort_unless($response->successful(), 502, 'El proveedor STT no pudo transcribir el audio.');
        $text = $response->json('text');
        abort_unless(is_string($text) && trim($text) !== '', 422, 'No se reconoció voz. Intentá de nuevo o escribí tu pregunta.');
        abort_if(mb_strlen($text) > 500, 422, 'La transcripción excede 500 caracteres. Grabá una pregunta más corta.');

        return trim($text);
    }

    public function synthesize(string $text): string
    {
        try {
            $response = $this->client('tts')->post(config('audio.tts.url'), [
                'model' => config('audio.tts.model'), 'voice' => config('audio.tts.voice'),
                'input' => $text, 'response_format' => 'mp3',
            ]);
        } catch (ConnectionException) {
            abort(502, 'No se pudo conectar con el proveedor TTS. La respuesta escrita sigue disponible.');
        }
        $bytes = $response->body();
        $type = strtolower(explode(';', $response->header('Content-Type'))[0]);
        abort_unless($response->successful() && in_array($type, ['audio/mpeg', 'audio/mp3'], true)
            && strlen($bytes) > 3 && strlen($bytes) <= 8 * 1024 * 1024, 502, 'El proveedor TTS no devolvió audio válido.');
        abort_unless(str_starts_with($bytes, 'ID3') || (ord($bytes[0]) === 255 && (ord($bytes[1]) & 224) === 224), 502, 'El proveedor TTS no devolvió un MP3 válido.');

        return $bytes;
    }

    private function client(string $kind): PendingRequest
    {
        abort_unless(config("audio.{$kind}.provider") === 'compatible', 503, 'Este proveedor de audio no está disponible en el servidor. Usá la entrada de texto.');
        $url = config("audio.{$kind}.url");
        abort_unless(is_string($url) && filter_var($url, FILTER_VALIDATE_URL)
            && in_array(parse_url($url, PHP_URL_SCHEME), ['http', 'https'], true)
            && filled(config("audio.{$kind}.model")), 503, 'El proveedor de audio no está configurado.');
        $http = Http::connectTimeout(5)->timeout(40)->withoutRedirecting();
        if (filled(config("audio.{$kind}.key"))) {
            $http->withToken(config("audio.{$kind}.key"));
        }

        return $http;
    }
}
