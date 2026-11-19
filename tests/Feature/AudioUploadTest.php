<?php

namespace Tests\Feature;

use App\Support\Audio\AudioUpload;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Process;
use Illuminate\Validation\ValidationException;
use Symfony\Component\Process\ExecutableFinder;
use Tests\TestCase;

class AudioUploadTest extends TestCase
{
    public function test_real_wav_content_is_validated_without_trusting_client_duration(): void
    {
        $this->requireProbe();
        $file = $this->wav(1);
        try {
            app(AudioUpload::class)->validate($file);
            $this->assertFileExists($file->getPathname());
        } finally {
            unlink($file->getPathname());
        }
    }

    public function test_real_audio_over_the_limit_is_rejected(): void
    {
        $this->requireProbe();
        $file = $this->wav(31);
        $this->expectException(ValidationException::class);
        try {
            app(AudioUpload::class)->validate($file);
        } finally {
            unlink($file->getPathname());
        }
    }

    public function test_real_invalid_content_is_rejected(): void
    {
        $this->requireProbe();
        $file = UploadedFile::fake()->createWithContent('recording.wav', 'Not an audio recording');
        $this->expectException(ValidationException::class);
        try {
            app(AudioUpload::class)->validate($file);
        } finally {
            unlink($file->getPathname());
        }
    }

    public function test_live_webm_without_container_duration_uses_packet_timing(): void
    {
        $this->requireProbe();
        $configured = getenv('SEBRA_TEST_FFMPEG') ?: 'ffmpeg';
        $ffmpeg = is_executable($configured) ? $configured : (new ExecutableFinder)->find($configured);
        if (! $ffmpeg) {
            $this->markTestSkipped('Set SEBRA_TEST_FFMPEG to generate a real MediaRecorder-style WebM fixture.');
        }
        $path = tempnam(sys_get_temp_dir(), 'sebra-audio-test-');
        try {
            $result = Process::timeout(10)->run([$ffmpeg, '-v', 'error', '-y', '-f', 'lavfi', '-i', 'anullsrc=r=16000:cl=mono', '-t', '1', '-c:a', 'libopus', '-f', 'webm', '-live', '1', $path]);
            $this->assertTrue($result->successful(), $result->errorOutput());
            app(AudioUpload::class)->validate(new UploadedFile($path, 'recording.webm', null, null, true));
        } finally {
            unlink($path);
        }
    }

    private function requireProbe(): void
    {
        $configured = config('audio.ffprobe');
        $binary = is_executable($configured) ? $configured : (new ExecutableFinder)->find($configured);
        if (! $binary) {
            $this->markTestSkipped('Set AUDIO_FFPROBE to run real media validation tests.');
        }
    }

    private function wav(int $seconds): UploadedFile
    {
        $data = str_repeat("\0", 16000 * $seconds);

        return UploadedFile::fake()->createWithContent('recording.wav', 'RIFF'.pack('V', 36 + strlen($data)).'WAVEfmt '.pack('VvvVVvv', 16, 1, 1, 8000, 16000, 2, 16).'data'.pack('V', strlen($data)).$data);
    }
}
