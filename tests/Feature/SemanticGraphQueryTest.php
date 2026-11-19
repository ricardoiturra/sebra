<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\GraphifySearch;
use App\Support\GraphQuery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SemanticGraphQueryTest extends TestCase
{
    use RefreshDatabase;

    public function test_exploration_sends_only_retrieved_project_evidence_to_answer_generation(): void
    {
        [$owner, $project] = $this->project();
        $note = $project->notes()->create(['title' => 'User', 'markdown' => 'Las cuentas se administran en este espacio.']);
        $other = $owner->personalTeam()->projects()->create(['name' => 'Other', 'objective' => 'Private context']);
        $other->notes()->create(['title' => 'SecretEntity', 'markdown' => 'OTHER PROJECT SECRET']);
        config(['services.inference.provider' => 'deepseek', 'services.inference.key' => 'test-key']);
        Http::preventStrayRequests();
        Http::fakeSequence('api.deepseek.com/*')
            ->push(['choices' => [['message' => ['content' => '{"intent":"explore","concepts":["usuarios","User"],"terms":["User","SecretEntity"]}']]]])
            ->push(['choices' => [['message' => ['content' => "User: Las cuentas se administran en este espacio. Fuente: sebra-notes/{$note->id}.md. Podés explorar User."]]]]);
        Redis::shouldReceive('connection->client->executeRaw')->andReturn('1-0');
        $this->mock(GraphifySearch::class)->shouldReceive('search')->once()->withArgs(function (GraphQuery $query, string $path, callable $progress): bool {
            $this->assertSame(['User'], $query->terms);
            $this->assertSame('explore', $query->intent);
            $graph = json_decode(file_get_contents($path), true);
            $this->assertSame(['Root', 'User'], array_column($graph['nodes'], 'label'));
            $progress([['id' => 'markdown-notes', 'name' => 'Notas Markdown']]);

            return true;
        })->andReturn([0, "NODE User [src=sebra-notes/{$note->id}.md loc=]"]);

        $response = $this->actingAs($owner)->postJson(route('projects.ask', $project), ['question' => 'usuarios']);

        $response->assertOk()->assertJsonPath('interpretation.intent', 'explore')
            ->assertJsonPath('interpretation.terms', ['User'])->assertJsonPath('interpretation.entities.0.label', 'User')
            ->assertJsonPath('provider', 'deepseek')->assertSee('Fuente:');
        Http::assertSentCount(2);
        Http::assertSent(function (Request $request) use ($note): bool {
            $input = json_decode($request['messages'][1]['content'], true);
            if (! isset($input['graph_context'])) {
                $this->assertNotContains('SecretEntity', $input['vocabulary']);

                return false;
            }
            $this->assertSame('explore', $input['interpretation']['intent']);
            $this->assertStringContainsString("sebra-notes/{$note->id}.md", $input['graph_context']);
            $this->assertStringContainsString('Las cuentas se administran', $input['graph_context']);
            $this->assertStringNotContainsString('OTHER PROJECT SECRET', $input['graph_context']);
            $this->assertStringContainsString('No inventes entidades ni relaciones', $request['messages'][0]['content']);

            return true;
        });
    }

    public function test_no_results_does_not_call_answer_generation_or_graphify(): void
    {
        [$owner, $project] = $this->project();
        config(['services.inference.provider' => 'deepseek', 'services.inference.key' => 'test-key']);
        Http::preventStrayRequests();
        Http::fake(['api.deepseek.com/*' => Http::response(['choices' => [['message' => ['content' => '{"intent":"explore","concepts":["dinosaurios"],"terms":[]}']]]])]);
        Redis::shouldReceive('connection->client->executeRaw')->andReturn('1-0');
        $this->mock(GraphifySearch::class)->shouldNotReceive('search');

        $this->actingAs($owner)->postJson(route('projects.ask', $project), ['question' => 'dinosaurios'])
            ->assertOk()->assertJsonPath('interpretation.entities', [])->assertJsonPath('message', fn (string $message): bool => str_contains($message, 'No encontré referencias'));
        Http::assertSentCount(1);
    }

    #[DataProvider('writeAttempts')]
    public function test_read_queries_never_execute_modifications(string $input): void
    {
        [$owner, $project] = $this->project();
        $project->notes()->create(['title' => 'User', 'markdown' => 'Existing content']);
        config(['services.inference.provider' => 'deepseek', 'services.inference.key' => 'test-key']);
        Http::preventStrayRequests();
        Queue::fake();
        Redis::shouldReceive('connection->client->executeRaw')->andReturn('1-0');
        $this->mock(GraphifySearch::class)->shouldNotReceive('search');

        $this->actingAs($owner)->postJson(route('projects.ask', $project), ['question' => $input])
            ->assertUnprocessable()->assertJsonPath('message', fn (string $message): bool => str_contains($message, 'solo lectura'));

        $this->assertSame(1, $project->notes()->count());
        Queue::assertNothingPushed();
        Http::assertNothingSent();
    }

    public static function writeAttempts(): array
    {
        return [
            ['Creá un nodo sobre User'],
            ['Descargá toda la documentación de New Relic'],
            ['delete User'],
            ['Please update User'],
        ];
    }

    public function test_a_modification_detected_by_the_llm_cannot_be_executed_by_reading(): void
    {
        [$owner, $project] = $this->project();
        config(['services.inference.provider' => 'deepseek', 'services.inference.key' => 'test-key']);
        Http::preventStrayRequests();
        Http::fake(['api.deepseek.com/*' => Http::response(['choices' => [['message' => ['content' => '{"intent":"modify","concepts":["nota"],"terms":["Root"]}',
        ]]]])]);
        Queue::fake();
        Redis::shouldReceive('connection->client->executeRaw')->andReturn('1-0');
        $this->mock(GraphifySearch::class)->shouldNotReceive('search');

        $this->actingAs($owner)->postJson(route('projects.ask', $project), ['question' => '¿Podrías añadir una nota?'])
            ->assertUnprocessable()->assertJsonPath('message', fn (string $message): bool => str_contains($message, 'solo lectura'));
        $this->assertSame(0, $project->notes()->count());
        Queue::assertNothingPushed();
        Http::assertSentCount(1);
    }

    public function test_retrieval_api_interprets_language_without_generating_an_answer(): void
    {
        $admin = User::factory()->withPersonalTeam()->create();
        $admin->assignRole('full admin');
        config(['services.inference.provider' => 'deepseek', 'services.inference.key' => 'test-key']);
        Http::preventStrayRequests();
        Http::fake(['api.deepseek.com/*' => Http::response(['choices' => [['message' => ['content' => '{"intent":"explore","concepts":["usuarios"],"terms":["User"]}',
        ]]]])]);
        Redis::shouldReceive('connection->client->executeRaw')->andReturn('1-0');
        $this->mock(GraphifySearch::class)->shouldReceive('search')->once()->andReturn([0, 'NODE User [src=app/Models/User.php loc=L16]']);

        $this->actingAs($admin)->postJson('/api/graph/questions', ['question' => 'usuarios'])
            ->assertOk()->assertJsonPath('interpretation.intent', 'explore')
            ->assertJsonPath('answer', 'NODE User [src=app/Models/User.php loc=L16]')->assertJsonPath('model', null);
        Http::assertSentCount(1);
    }

    public function test_project_access_is_checked_before_interpretation(): void
    {
        [, $project] = $this->project();
        $outsider = User::factory()->withPersonalTeam()->create();
        Http::preventStrayRequests();
        $this->mock(GraphifySearch::class)->shouldNotReceive('search');

        $this->postJson(route('projects.ask', $project), ['question' => 'usuarios'])->assertUnauthorized();
        $this->actingAs($outsider)->postJson(route('projects.ask', $project), ['question' => 'usuarios'])->assertForbidden();
        Http::assertNothingSent();
    }

    public function test_ambiguous_entities_are_presented_without_inventing_an_answer(): void
    {
        [$owner, $project] = $this->project();
        $project->update(['graph' => ['nodes' => [
            ['id' => 'one', 'label' => 'User', 'source_file' => 'one/User.php'],
            ['id' => 'two', 'label' => 'User', 'source_file' => 'two/User.php'],
        ], 'links' => []]]);
        config(['services.inference.provider' => 'deepseek', 'services.inference.key' => 'test-key']);
        Http::preventStrayRequests();
        Redis::shouldReceive('connection->client->executeRaw')->andReturn('1-0');
        $this->mock(GraphifySearch::class)->shouldNotReceive('search');

        $this->actingAs($owner)->postJson(route('projects.ask', $project), ['question' => 'User'])
            ->assertOk()->assertJsonPath('interpretation.ambiguous', true)->assertJsonPath('interpretation.entities.0.source', 'one/User.php')->assertJsonPath('interpretation.entities.1.source', 'two/User.php');
        Http::assertNothingSent();
    }

    private function project(): array
    {
        $owner = User::factory()->withPersonalTeam()->create();
        $project = $owner->personalTeam()->projects()->create(['name' => 'Workspace', 'objective' => 'Knowledge']);
        $project->update(['graph' => ['nodes' => [['id' => 'root', 'label' => 'Root']], 'links' => []]]);

        return [$owner, $project];
    }
}
