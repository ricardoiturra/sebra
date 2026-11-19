<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Support\Audio\AudioUpload;
use App\Support\Audio\SpeechToText;
use App\Support\Audio\SpokenText;
use App\Support\Audio\TextToSpeech;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\UploadedFile;

class AudioController extends Controller
{
    public function transcribe(Request $request, AudioUpload $inspector, SpeechToText $stt, ?Project $project = null): JsonResponse
    {
        $file = $request->file('audio');
        try {
            $request->validate([
                'consent' => ['required', 'accepted'],
                'audio' => ['required', 'file', 'max:'.config('audio.max_kilobytes'),
                    'mimetypes:audio/webm,video/webm,audio/ogg,application/ogg,audio/wav,audio/x-wav,audio/mpeg,audio/mp4,video/mp4,audio/x-m4a,audio/flac,audio/x-flac'],
            ]);
            abort_unless(config('audio.stt.provider') === 'compatible', 503, 'STT de servidor no disponible. Podés usar el teclado.');
            $inspector->validate($file);

            return response()->json(['text' => $stt->transcribe($file)], 200, ['Cache-Control' => 'no-store, private']);
        } finally {
            if ($file instanceof UploadedFile && is_file($file->getPathname())) {
                unlink($file->getPathname());
            }
        }
    }

    public function speak(Request $request, SpokenText $spokenText, TextToSpeech $tts, ?Project $project = null): Response
    {
        $input = $request->validate(['consent' => ['required', 'accepted'], 'text' => ['required', 'string', 'max:16000']]);
        $text = $spokenText->prepare($input['text']);
        abort_if($text === '', 422, 'No hay texto para leer.');

        return response($tts->synthesize($text), 200, [
            'Content-Type' => 'audio/mpeg', 'Cache-Control' => 'no-store, private',
            'X-Content-Type-Options' => 'nosniff', 'Content-Disposition' => 'inline; filename="sebra-response.mp3"',
        ]);
    }
}
