<?php

namespace Tests\Feature;

use App\Jobs\ImportNewRelicDocs;
use App\Models\User;
use App\Support\ProjectGraph;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

class ProjectNotesTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_member_can_add_markdown_to_their_projects_graph(): void
    {
        [$owner, $project] = $this->project();
        $outsider = User::factory()->create();
        $payload = ['title' => 'Guía de alertas', 'markdown' => 'Contenido **privado** <script>alert(1)</script>'];

        $this->actingAs($outsider)->postJson(route('projects.notes.store', $project), $payload)->assertForbidden();
        Redis::shouldReceive('connection->client->executeRaw')
            ->once()->withArgs(fn (array $args) => $args[1] === $project->streamPrefix().':graph')->andReturn('1-0');

        $this->actingAs($owner)->post(route('projects.notes.store', $project), $payload)->assertRedirect(route('projects.show', $project));
        $note = $project->notes()->sole();
        $this->getJson(route('projects.graph', $project))->assertJsonPath('nodes.0.label', $payload['title'])->assertDontSee('Contenido');
        $this->get(route('projects.show', [$project, 'q' => 'alertas']))->assertOk()->assertSee('<strong>privado</strong>', false)->assertDontSee('<script>alert(1)</script>', false);
        $this->assertStringContainsString($payload['markdown'], ProjectGraph::noteContext($project, "NODE Alertas [src=sebra-notes/{$note->id}.md loc=]"));
    }

    public function test_ai_creates_a_node_once_for_the_same_content(): void
    {
        [$owner, $project] = $this->project();
        config(['services.inference.provider' => 'deepseek', 'services.inference.key' => 'test-key']);
        Http::preventStrayRequests();
        Http::fake(['api.deepseek.com/*' => Http::response([
            'choices' => [['message' => ['content' => '{"title":"Alertas","markdown":"Usar la política del equipo."}']]],
        ])]);
        Redis::shouldReceive('connection->client->executeRaw')->once()->andReturn('1-0');

        $data = ['question' => 'Creá un nodo sobre alertas.'];
        $url = route('projects.actions', $project);
        $this->actingAs($owner)->postJson($url, $data)->assertOk()->assertJsonPath('message', 'Nodo añadido al grafo.');
        $this->postJson($url, $data)->assertOk()->assertJsonPath('message', 'Ya existe un nodo con ese contenido.');
        $this->assertSame(1, $project->notes()->count());
    }

    public function test_only_team_administrators_can_queue_the_official_docs_import(): void
    {
        [$owner, $project] = $this->project();
        $editor = User::factory()->create();
        $project->team->users()->attach($editor, ['role' => 'editor']);
        Queue::fake();
        $data = ['question' => 'Descargá toda la documentación oficial más reciente de New Relic e importala a este proyecto.'];
        $url = route('projects.actions', $project);

        $this->actingAs($editor)->postJson($url, $data)->assertForbidden();
        Queue::assertNothingPushed();
        $this->actingAs($owner)->postJson($url, $data)->assertAccepted();
        Queue::assertPushed(ImportNewRelicDocs::class, fn ($job) => $job->projectId === $project->id && $job->userId === $owner->id);
        $this->postJson(route('projects.ask', $project), ['question' => '¿Cómo descargo la documentación de New Relic?'])->assertUnprocessable();
        Queue::assertPushed(ImportNewRelicDocs::class, 1);
    }

    public function test_import_preserves_source_and_skips_unchanged_documents(): void
    {
        [$owner, $project] = $this->project();
        $body = "---\ntitle: Alertas\n---\nContenido";
        $sha = sha1('blob '.strlen($body)."\0".$body);
        $path = 'src/content/docs/alerts/alertas.mdx';
        $commit = str_repeat('a', 40);
        $tree = str_repeat('b', 40);
        Http::preventStrayRequests();
        Http::fake([
            'api.github.com/*/commits/develop' => Http::response(['sha' => $commit, 'commit' => ['tree' => ['sha' => $tree]]]),
            'api.github.com/*/git/trees/*' => Http::response(['truncated' => false, 'tree' => [['type' => 'blob', 'path' => $path, 'sha' => $sha]]]),
            'raw.githubusercontent.com/*' => Http::response($body),
        ]);
        Redis::shouldReceive('connection->client->executeRaw')->times(5)->andReturn('1-0');

        $job = new ImportNewRelicDocs($project->id, $owner->id);
        $job->handle();
        $job->handle();

        Http::assertSentCount(5);
        $note = $project->notes()->sole();
        $this->assertSame($body, $note->markdown);
        $this->assertStringEndsWith($path, $note->sourceUrl());
        $this->assertSame('New Relic · Alerts', ProjectGraph::forProject($project, forDisplay: true)['nodes'][0]['label']);
    }

    public function test_search_is_the_only_node_list_and_is_scoped_to_the_project(): void
    {
        [$owner, $project] = $this->project();
        [, $otherProject] = $this->project();
        $otherProject->notes()->create(['title' => 'NRQL externo', 'markdown' => 'Privado']);
        $project->notes()->create(['title' => 'Hallazgo de cuerpo', 'markdown' => 'Contenido NRQL']);
        $project->notes()->create(['title' => 'Sin coincidencias', 'markdown' => 'Otro contenido']);

        $url = route('projects.show', $project);
        $this->actingAs($owner)->get($url.'?page=2')
            ->assertOk()->assertSee('Buscar por título o contenido')->assertDontSee('Hallazgo de cuerpo')
            ->assertDontSee('Añadir nodo Markdown')->assertDontSee('Importar graph.json');
        $this->get($url.'?q=nrql')->assertOk()->assertSee('Hallazgo de cuerpo')->assertDontSee('NRQL externo')
            ->assertDontSee('Sin coincidencias')->assertDontSee('Paginación de notas');
        $this->get($url.'?q=%25')->assertOk()->assertSee('No se encontraron nodos');
    }

    private function project(): array
    {
        $owner = User::factory()->withPersonalTeam()->create();

        return [$owner, $owner->personalTeam()->projects()->create(['name' => 'NewRelic', 'objective' => 'Organizar la documentación'])];
    }
}
