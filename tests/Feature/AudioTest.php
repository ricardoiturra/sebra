<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\Audio\SpokenText;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AudioTest extends TestCase
{
    use RefreshDatabase;

    #[DataProvider('operations')]
    public function test_audio_requires_authentication_and_team_membership(string $operation): void
    {
        [, $project] = $this->project();
        $outsider = User::factory()->withPersonalTeam()->create();
        Http::preventStrayRequests();
        $url = route('projects.audio.'.$operation, $project);
        $this->postJson($url, [])->assertUnauthorized();
        $this->actingAs($outsider)->postJson($url, [])->assertForbidden();
        Http::assertNothingSent();
    }

    public static function operations(): array
    {
        return [['transcribe'], ['speak']];
    }

    public function test_global_audio_requires_full_admin(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $this->actingAs($user)->postJson(route('audio.speak'), ['text' => 'Hola', 'consent' => true])->assertForbidden();
        $this->postJson(route('audio.transcribe'), [])->assertForbidden();
    }

    public function test_transcription_uses_backend_credentials_and_cleans_the_upload(): void
    {
        [$owner, $project] = $this->project();
        $this->configureProviders();
        $this->validProbe();
        Http::preventStrayRequests();
        Http::fake(['https://audio.example/transcriptions' => Http::response(['text' => '¿Cómo funcionan los equipos?'])]);
        $file = $this->wav();
        $path = $file->getPathname();

        $this->actingAs($owner)->post(route('projects.audio.transcribe', $project), ['audio' => $file, 'consent' => '1'], ['Accept' => 'application/json'])
            ->assertOk()->assertJsonPath('text', '¿Cómo funcionan los equipos?')->assertHeader('Cache-Control', 'no-store, private');

        $this->assertFileDoesNotExist($path);
        Http::assertSent(fn (Request $request): bool => $request->hasHeader('Authorization', 'Bearer backend-stt-key') && collect($request->data())->contains(fn ($part): bool => is_array($part) && ($part['name'] ?? null) === 'language' && $part['contents'] === 'es'));
        Process::assertRan(fn ($process): bool => in_array('stream=codec_type:format=duration:packet=pts_time,duration_time', $process->command, true));
        $this->assertSame(0, $project->notes()->count());
    }

    #[DataProvider('invalidUploads')]
    public function test_invalid_uploads_are_rejected_before_external_requests(string $kind): void
    {
        [$owner, $project] = $this->project();
        $this->configureProviders();
        Http::preventStrayRequests();
        Process::fake();
        $file = match ($kind) {
            'fake-format' => UploadedFile::fake()->createWithContent('recording.webm', '<?php echo "not audio";'),
            'too-large' => UploadedFile::fake()->create('recording.wav', 5121, 'audio/wav'),
            default => $this->wav(),
        };
        if ($kind === 'fake-format') {
            $fake = $file;
            $file = new UploadedFile($fake->getPathname(), 'recording.webm', null, null, true);
        }
        $path = $file->getPathname();
        $data = ['audio' => $file, 'consent' => $kind !== 'no-consent'];
        $this->actingAs($owner)->post(route('projects.audio.transcribe', $project), $data, ['Accept' => 'application/json'])
            ->assertUnprocessable()->assertJsonValidationErrors($kind === 'no-consent' ? 'consent' : 'audio');
        Http::assertNothingSent();
        Process::assertNothingRan();
        $this->assertFileDoesNotExist($path);
    }

    public static function invalidUploads(): array
    {
        return [['fake-format'], ['too-large'], ['no-consent']];
    }

    #[DataProvider('invalidProbes')]
    public function test_real_media_constraints_are_enforced(array $probe, int $exit): void
    {
        [$owner, $project] = $this->project();
        $this->configureProviders();
        Process::fake(['*' => Process::result(output: json_encode($probe), exitCode: $exit)]);
        Http::preventStrayRequests();
        $file = $this->wav();
        $this->actingAs($owner)->post(route('projects.audio.transcribe', $project), ['audio' => $file, 'consent' => true], ['Accept' => 'application/json'])
            ->assertUnprocessable()->assertJsonValidationErrors('audio');
        Http::assertNothingSent();
        Process::assertRanTimes(fn ($process): bool => true, 1);
    }

    public static function invalidProbes(): array
    {
        return [
            [['streams' => [['codec_type' => 'audio']], 'format' => ['duration' => '31']], 0],
            [['streams' => [['codec_type' => 'video']], 'format' => ['duration' => '1']], 0],
            [['streams' => [['codec_type' => 'audio']], 'format' => ['duration' => '0']], 0],
            [[], 1],
        ];
    }

    #[DataProvider('sttFailures')]
    public function test_stt_errors_do_not_store_audio_or_call_the_query_pipeline(array $body, int $status, int $expected): void
    {
        [$owner, $project] = $this->project();
        $this->configureProviders();
        $this->validProbe();
        Http::preventStrayRequests();
        Http::fake(['https://audio.example/transcriptions' => Http::response($body, $status)]);
        $file = $this->wav();
        $path = $file->getPathname();
        $this->actingAs($owner)->post(route('projects.audio.transcribe', $project), ['audio' => $file, 'consent' => true], ['Accept' => 'application/json'])
            ->assertStatus($expected);
        $this->assertFileDoesNotExist($path);
        $this->assertSame(0, $project->notes()->count());
        Http::assertSentCount(1);
    }

    public static function sttFailures(): array
    {
        return [[[], 503, 502], [['text' => ''], 200, 422], [['text' => str_repeat('a', 501)], 200, 422]];
    }

    public function test_unavailable_providers_fail_without_external_requests(): void
    {
        [$owner, $project] = $this->project();
        config(['audio.stt.provider' => 'disabled', 'audio.tts.provider' => 'disabled']);
        Http::preventStrayRequests();
        $this->actingAs($owner)->post(route('projects.audio.transcribe', $project), ['audio' => $this->wav(), 'consent' => true], ['Accept' => 'application/json'])->assertStatus(503);
        $this->postJson(route('projects.audio.speak', $project), ['text' => 'Hola', 'consent' => true])->assertStatus(503);
        $this->get(route('projects.show', $project))->assertOk()->assertSee('graph-question')->assertDontSee('backend-stt-key');
        Http::assertNothingSent();
    }

    public function test_missing_media_validator_fails_closed(): void
    {
        [$owner, $project] = $this->project();
        $this->configureProviders();
        Process::fake(['*' => Process::result(exitCode: 127)]);
        Http::preventStrayRequests();
        $this->actingAs($owner)->post(route('projects.audio.transcribe', $project), ['audio' => $this->wav(), 'consent' => true], ['Accept' => 'application/json'])->assertStatus(503);
        Process::assertRanTimes(fn ($process): bool => true, 1);
        Http::assertNothingSent();
    }

    public function test_connection_failures_return_recoverable_errors_and_clean_uploads(): void
    {
        [$owner, $project] = $this->project();
        $this->configureProviders();
        $this->validProbe();
        Http::preventStrayRequests();
        Http::fake([
            'https://audio.example/transcriptions' => Http::failedConnection(),
            'https://audio.example/speech' => Http::failedConnection(),
        ]);
        $file = $this->wav();
        $path = $file->getPathname();
        $this->actingAs($owner)->post(route('projects.audio.transcribe', $project), ['audio' => $file, 'consent' => true], ['Accept' => 'application/json'])->assertStatus(502);
        $this->assertFileDoesNotExist($path);
        $this->postJson(route('projects.audio.speak', $project), ['text' => 'Hola', 'consent' => true])->assertStatus(502);
        Http::assertSentCount(2);
        Process::assertRanTimes(fn ($process): bool => true, 1);
    }

    public function test_tts_preserves_text_but_omits_code_and_paths_from_speech(): void
    {
        [$owner, $project] = $this->project();
        $this->configureProviders();
        Http::preventStrayRequests();
        Http::fake(['https://audio.example/speech' => Http::response('ID3fake-mp3-audio', 200, ['Content-Type' => 'audio/mpeg'])]);
        $text = "Los equipos agrupan usuarios.\n```php\nsecret_function();\n```\nFuente: [User](https://example.com/code) app/Models/User.php:15";
        $this->actingAs($owner)->postJson(route('projects.audio.speak', $project), ['text' => $text, 'consent' => true])
            ->assertOk()->assertHeader('Content-Type', 'audio/mpeg')->assertHeader('Cache-Control', 'no-store, private');
        Http::assertSent(function (Request $request): bool {
            $this->assertStringContainsString('Los equipos agrupan usuarios.', $request['input']);
            $this->assertStringNotContainsString('secret_function', $request['input']);
            $this->assertStringNotContainsString('app/Models', $request['input']);
            $this->assertStringNotContainsString('https://', $request['input']);

            return $request->hasHeader('Authorization', 'Bearer backend-tts-key');
        });
    }

    #[DataProvider('ttsFailures')]
    public function test_tts_failures_are_recoverable_and_no_audio_is_persisted(string $body, int $status, string $type): void
    {
        [$owner, $project] = $this->project();
        $this->configureProviders();
        Http::preventStrayRequests();
        Http::fake(['https://audio.example/speech' => Http::response($body, $status, ['Content-Type' => $type])]);
        $this->actingAs($owner)->postJson(route('projects.audio.speak', $project), ['text' => 'Hola', 'consent' => true])->assertStatus(502);
        Http::assertSentCount(1);
    }

    public static function ttsFailures(): array
    {
        return [['', 200, 'audio/mpeg'], ['provider error', 500, 'application/json'], ['not audio', 200, 'audio/mpeg']];
    }

    public function test_tts_rejects_empty_text_and_missing_consent(): void
    {
        [$owner, $project] = $this->project();
        Http::preventStrayRequests();
        $this->actingAs($owner)->postJson(route('projects.audio.speak', $project), ['text' => '', 'consent' => true])->assertUnprocessable();
        $this->postJson(route('projects.audio.speak', $project), ['text' => 'Hola'])->assertUnprocessable()->assertJsonValidationErrors('consent');
        Http::assertNothingSent();
    }

    public function test_user_quota_is_shared_across_projects(): void
    {
        [$owner, $project] = $this->project();
        $other = $owner->personalTeam()->projects()->create(['name' => 'Other', 'objective' => 'Other']);
        config(['audio.requests_per_day' => 1]);
        Http::preventStrayRequests();
        $this->actingAs($owner)->postJson(route('projects.audio.speak', $project), ['text' => '', 'consent' => true])->assertUnprocessable();
        $this->postJson(route('projects.audio.speak', $other), ['text' => '', 'consent' => true])->assertStatus(429);
        Http::assertNothingSent();
    }

    public function test_team_quota_is_shared_by_its_members_and_not_other_teams(): void
    {
        [$owner, $project] = $this->project();
        $member = User::factory()->withPersonalTeam()->create();
        $owner->personalTeam()->users()->attach($member, ['role' => 'editor']);
        config(['audio.team_requests_per_day' => 1]);
        Http::preventStrayRequests();
        $this->actingAs($owner)->postJson(route('projects.audio.speak', $project), [])->assertUnprocessable();
        $this->actingAs($member)->postJson(route('projects.audio.speak', $project), [])->assertStatus(429);
        $personal = $member->personalTeam()->projects()->create(['name' => 'Own', 'objective' => 'Own']);
        $this->postJson(route('projects.audio.speak', $personal), [])->assertUnprocessable();
        Http::assertNothingSent();
    }

    public function test_speech_omits_repeated_technical_references_without_repetitive_announcements(): void
    {
        $markdown = "Los `User` pertenecen a `Team`.\n\nFuentes: `app/Models/User.php`, `app/Models/Team.php`.\n"
            ."Consultá los permisos de `Team`. El acceso está protegido.\n```php\nGate::authorize('view', \$project);\n```";
        $speech = app(SpokenText::class)->prepare($markdown);

        $this->assertSame('Los User pertenecen a Team. Consultá los permisos de Team. El acceso está protegido.', $speech);
        $this->assertStringNotContainsString('Referencia', $speech);
        $this->assertStringNotContainsString('pantalla', $speech);
    }

    public function test_an_outsider_cannot_consume_another_teams_audio_quota(): void
    {
        [$owner, $project] = $this->project();
        $outsider = User::factory()->withPersonalTeam()->create();
        config(['audio.team_requests_per_day' => 1]);
        Http::preventStrayRequests();
        $this->actingAs($outsider)->postJson(route('projects.audio.speak', $project), [])->assertForbidden();
        $this->actingAs($owner)->postJson(route('projects.audio.speak', $project), [])->assertUnprocessable();
        Http::assertNothingSent();
    }

    public function test_spoken_text_is_bounded(): void
    {
        $this->assertLessThanOrEqual(3000, mb_strlen(app(SpokenText::class)->prepare(str_repeat('Contenido ', 600))));
    }

    private function project(): array
    {
        $owner = User::factory()->withPersonalTeam()->create();

        return [$owner, $owner->personalTeam()->projects()->create(['name' => 'Voice', 'objective' => 'Knowledge'])];
    }

    private function configureProviders(): void
    {
        config([
            'audio.stt.provider' => 'compatible', 'audio.tts.provider' => 'compatible',
            'audio.stt.url' => 'https://audio.example/transcriptions', 'audio.stt.key' => 'backend-stt-key',
            'audio.tts.url' => 'https://audio.example/speech', 'audio.tts.key' => 'backend-tts-key',
        ]);
    }

    private function validProbe(): void
    {
        Process::fake(['*' => Process::result(output: '{"streams":[{"codec_type":"audio"}],"format":{"duration":"1"}}')]);
    }

    private function wav(): UploadedFile
    {
        $data = str_repeat("\0", 16000);
        $body = 'RIFF'.pack('V', 36 + strlen($data)).'WAVEfmt '.pack('VvvVVvv', 16, 1, 1, 8000, 16000, 2, 16).'data'.pack('V', strlen($data)).$data;

        return UploadedFile::fake()->createWithContent('recording.wav', $body);
    }
}
