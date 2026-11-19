<?php

namespace Tests\Feature;

use App\Support\GraphInference;
use App\Support\GraphQueryInterpreter;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

class GraphQueryInterpreterTest extends TestCase
{
    #[DataProvider('literalQueries')]
    public function test_literal_entities_are_resolved_without_provider_calls(string $input, string $intent, array $terms): void
    {
        Http::preventStrayRequests();
        $query = app(GraphQueryInterpreter::class)->interpret($input, $this->graph(), app(GraphInference::class));

        $this->assertSame($intent, $query->intent);
        $this->assertSame($terms, $query->terms);
        $this->assertNotEmpty($query->entities);
        Http::assertNothingSent();
    }

    public static function literalQueries(): array
    {
        return [
            ['User', 'explore', ['User']],
            ['CreateNewUser', 'explore', ['CreateNewUser']],
            ['¿Cómo funciona User?', 'query', ['User']],
            ['¿Qué relación tienen User y Team?', 'navigate', ['User', 'Team']],
            ['show User', 'navigate', ['User']],
        ];
    }

    #[DataProvider('synonyms')]
    public function test_synonyms_are_interpreted_as_graph_vocabulary(string $input, string $intent, array $terms): void
    {
        config(['services.inference.provider' => 'deepseek', 'services.inference.key' => 'test-key']);
        Http::preventStrayRequests();
        Http::fake(['api.deepseek.com/*' => Http::response(['choices' => [['message' => ['content' => json_encode([
            'intent' => $intent, 'concepts' => [$input, ...$terms], 'terms' => [...$terms, 'InventedEntity'],
        ])]]]])]);

        $query = app(GraphQueryInterpreter::class)->interpret($input, $this->graph(), app(GraphInference::class));

        $this->assertSame($intent, $query->intent);
        $this->assertSame($terms, $query->terms);
        $this->assertNotContains('InventedEntity', array_column($query->entities, 'label'));
        Http::assertSentCount(1);
    }

    public static function synonyms(): array
    {
        return [
            ['usuarios', 'explore', ['User']],
            ['users', 'explore', ['User']],
            ['accounts', 'explore', ['User']],
            ['¿Cómo se administran los usuarios?', 'query', ['User']],
            ['muéstrame la autenticación', 'navigate', ['Authentication']],
            ['¿Quién controla los permisos?', 'query', ['Permission']],
            ['explícame cómo funcionan los equipos', 'query', ['Team']],
        ];
    }

    public function test_duplicate_labels_require_disambiguation_without_inference(): void
    {
        Http::preventStrayRequests();
        $graph = ['nodes' => [
            ['id' => 'a', 'label' => 'User', 'source_file' => 'app/User.php'],
            ['id' => 'b', 'label' => 'User', 'source_file' => 'legacy/User.php'],
        ], 'links' => []];
        $query = app(GraphQueryInterpreter::class)->interpret('User', $graph, app(GraphInference::class));

        $this->assertTrue($query->ambiguous);
        $this->assertSame(['a', 'b'], array_column($query->entities, 'id'));
        Http::assertNothingSent();
    }

    public function test_exact_symbol_case_takes_priority_over_configuration_keys(): void
    {
        Http::preventStrayRequests();
        $query = app(GraphQueryInterpreter::class)->interpret('User', ['nodes' => [
            ['id' => 'config', 'label' => 'user', 'source_file' => 'compose.yaml'],
            ['id' => 'model', 'label' => 'User', 'source_file' => 'app/User.php'],
        ], 'links' => []], app(GraphInference::class));

        $this->assertSame(['User'], $query->terms);
        $this->assertFalse($query->ambiguous);
        $this->assertSame('model', $query->entities[0]['id']);
        Http::assertNothingSent();
    }

    public function test_a_source_path_disambiguates_labels_without_the_llm(): void
    {
        Http::preventStrayRequests();
        $query = app(GraphQueryInterpreter::class)->interpret('app/User.php', ['nodes' => [
            ['id' => 'a', 'label' => 'User', 'source_file' => 'app/User.php'],
            ['id' => 'b', 'label' => 'User', 'source_file' => 'legacy/User.php'],
        ], 'links' => []], app(GraphInference::class));

        $this->assertFalse($query->ambiguous);
        $this->assertSame(['app/User.php'], $query->terms);
        $this->assertSame(['a'], array_column($query->entities, 'id'));
        Http::assertNothingSent();
    }

    public function test_missing_concepts_do_not_become_confirmed_entities(): void
    {
        config(['services.inference.provider' => 'deepseek', 'services.inference.key' => 'test-key']);
        Http::preventStrayRequests();
        Http::fake(['api.deepseek.com/*' => Http::response(['choices' => [['message' => ['content' => '{"intent":"explore","concepts":["dinosaurs"],"terms":["Dinosaur"]}',
        ]]]])]);

        $query = app(GraphQueryInterpreter::class)->interpret('dinosaurios', $this->graph(), app(GraphInference::class));

        $this->assertSame([], $query->terms);
        $this->assertSame([], $query->entities);
        Http::assertSentCount(1);
    }

    public function test_invalid_interpretations_fail_closed(): void
    {
        config(['services.inference.provider' => 'deepseek', 'services.inference.key' => 'test-key']);
        Http::preventStrayRequests();
        Http::fake(['api.deepseek.com/*' => Http::response(['choices' => [['message' => ['content' => '{"intent":"execute","concepts":[],"terms":"--graph /other/graph.json"}',
        ]]]])]);
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('La IA no devolvió una interpretación válida.');

        app(GraphQueryInterpreter::class)->interpret('desconocido', $this->graph(), app(GraphInference::class));
    }

    public function test_an_isolated_topic_cannot_be_treated_as_an_incomplete_question(): void
    {
        config(['services.inference.provider' => 'deepseek', 'services.inference.key' => 'test-key']);
        Http::preventStrayRequests();
        Http::fake(['api.deepseek.com/*' => Http::response(['choices' => [['message' => ['content' => '{"intent":"query","concepts":["usuarios"],"terms":["User"]}',
        ]]]])]);
        $query = app(GraphQueryInterpreter::class)->interpret('usuarios', $this->graph(), app(GraphInference::class));
        $this->assertSame('explore', $query->intent);
        Http::assertSentCount(1);
    }

    private function graph(): array
    {
        return ['nodes' => array_map(fn (string $label): array => ['id' => $label, 'label' => $label], ['User', 'Team', 'Permission', 'Authentication', 'CreateNewUser']), 'links' => []];
    }
}
