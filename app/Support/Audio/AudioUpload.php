<?php

namespace App\Support\Audio;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Process;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class AudioUpload
{
    public function validate(UploadedFile $file): void
    {
        try {
            $result = Process::timeout(6)->run([
                config('audio.ffprobe'), '-v', 'error', '-protocol_whitelist', 'file',
                '-format_whitelist', 'matroska,webm,wav,mp3,ogg,mov,flac',
                '-show_entries', 'stream=codec_type:format=duration:packet=pts_time,duration_time',
                '-of', 'json', '-i', $file->getRealPath(),
            ]);
        } catch (RuntimeException) {
            abort(503, 'La validación de audio no está disponible. Podés usar el teclado.');
        }
        abort_if(in_array($result->exitCode(), [126, 127], true), 503, 'Falta ffprobe para validar el audio. Podés usar el teclado.');
        $data = json_decode($result->output(), true);
        $streams = $data['streams'] ?? [];
        if (! $result->successful() || count($streams) !== 1 || ($streams[0]['codec_type'] ?? null) !== 'audio') {
            throw ValidationException::withMessages(['audio' => 'El archivo debe contener solamente una pista de audio válida.']);
        }
        $times = [];
        $ends = [];
        foreach ($data['packets'] ?? [] as $packet) {
            if (is_numeric($packet['pts_time'] ?? null) && is_numeric($packet['duration_time'] ?? null)) {
                $times[] = (float) $packet['pts_time'];
                $ends[] = (float) $packet['pts_time'] + (float) $packet['duration_time'];
            }
        }
        $duration = $times === [] ? 0 : max($ends) - min($times);
        if (is_numeric($data['format']['duration'] ?? null)) {
            $duration = max($duration, (float) $data['format']['duration']);
        }
        if (! is_finite($duration) || $duration <= 0 || $duration > config('audio.max_seconds') + 0.5) {
            throw ValidationException::withMessages(['audio' => 'El audio está vacío o excede la duración máxima de 30 segundos.']);
        }
    }
}
