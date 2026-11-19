<?php

namespace App\Support\Audio;

interface TextToSpeech
{
    /** Return MP3 bytes without persisting them. */
    public function synthesize(string $text): string;
}
