<?php

return [
    'locale' => 'es-CL',
    'max_seconds' => 30,
    'max_kilobytes' => 5120,
    'max_speech_characters' => 3000,
    'ffprobe' => env('AUDIO_FFPROBE', 'ffprobe'),
    'requests_per_minute' => 10,
    'requests_per_day' => 100,
    'team_requests_per_day' => 500,
    'stt' => [
        'provider' => env('AUDIO_STT_PROVIDER', 'browser'),
        'url' => env('AUDIO_STT_URL', 'https://api.openai.com/v1/audio/transcriptions'),
        'key' => env('AUDIO_STT_KEY'),
        'model' => env('AUDIO_STT_MODEL', 'whisper-1'),
        'disclosure' => env('AUDIO_STT_DISCLOSURE', 'El audio se envía al servicio STT configurado en el servidor.'),
    ],
    'tts' => [
        'provider' => env('AUDIO_TTS_PROVIDER', 'browser'),
        'url' => env('AUDIO_TTS_URL', 'https://api.openai.com/v1/audio/speech'),
        'key' => env('AUDIO_TTS_KEY'),
        'model' => env('AUDIO_TTS_MODEL', 'tts-1'),
        'voice' => env('AUDIO_TTS_VOICE', 'alloy'),
        'disclosure' => env('AUDIO_TTS_DISCLOSURE', 'El texto de la versión oral se envía al servicio TTS configurado en el servidor.'),
    ],
];
