<?php

namespace App\Support\Audio;

use Illuminate\Http\UploadedFile;

interface SpeechToText
{
    public function transcribe(UploadedFile $audio): string;
}
