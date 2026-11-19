<?php

namespace Tests\Feature;

use App\Support\GraphInference;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class GraphInferenceTest extends TestCase
{
    #[DataProvider('providers')]
    public function test_it_sends_graph_context_to_the_configured_provider(string $provider, string $url, array $reply): void
    {
        config([
            'services.inference.provider' => $provider,
            'services.inference.model' => 'test-model',
            'services.inference.key' => $provider === 'ollama' ? null : 'test-key',
        ]);
        Http::preventStrayRequests();
        Http::fake([$url => Http::response(['model' => 'returned-model', ...$reply])]);

        $result = app(GraphInference::class)->answer('¿Cómo funciona Teams?', 'NODE Team [src=app/Models/Team.php]');

        $this->assertSame(['answer' => 'Respuesta', 'model' => 'returned-model', 'provider' => $provider], $result);
        Http::assertSent(function (Request $request) use ($url, $provider): bool {
            $messages = $request['messages'];
            $prompt = json_decode(end($messages)['content'], true);
            $this->assertSame($url, $request->url());
            $this->assertSame('test-model', $request['model']);
            $this->assertSame('¿Cómo funciona Teams?', $prompt['question']);
            $this->assertStringContainsString('app/Models/Team.php', $prompt['graph_context']);

            match ($provider) {
                'anthropic' => $this->assertTrue($request->hasHeader('x-api-key', 'test-key') && $request->hasHeader('anthropic-version')),
                'ollama' => $this->assertTrue(! $request->hasHeader('Authorization') && $request['stream'] === false),
                default => $this->assertTrue($request->hasHeader('Authorization', 'Bearer test-key')),
            };

            return true;
        });
    }

    public static function providers(): array
    {
        $completion = ['choices' => [['message' => ['content' => 'Respuesta']]]];

        return [
            'OpenAI' => ['openai', 'https://api.openai.com/v1/chat/completions', $completion],
            'Anthropic' => ['anthropic', 'https://api.anthropic.com/v1/messages', ['content' => [['type' => 'text', 'text' => 'Respuesta']]]],
            'OpenRouter' => ['openrouter', 'https://openrouter.ai/api/v1/chat/completions', $completion],
            'DeepSeek' => ['deepseek', 'https://api.deepseek.com/chat/completions', $completion],
            'Ollama' => ['ollama', 'http://host.docker.internal:11434/api/chat', ['message' => ['content' => 'Respuesta']]],
            'Groq' => ['groq', 'https://api.groq.com/openai/v1/chat/completions', $completion],
        ];
    }
}
