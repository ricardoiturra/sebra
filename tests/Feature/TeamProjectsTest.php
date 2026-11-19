<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Redis;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TeamProjectsTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_team_member_can_open_a_new_project_with_an_empty_graph(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $this->actingAs($user)->post('/projects', [
            'name' => 'Lanzamiento', 'objective' => 'Preparar la primera entrega.',
        ])->assertRedirect();

        $project = Project::sole();
        $this->assertSame($user->personalTeam()->id, $project->team_id);
        $this->get('/dashboard')->assertOk()->assertSee('Lanzamiento');
        $this->get(route('projects.show', $project))->assertOk()->assertSee('Preparar la primera entrega.');
        $this->getJson(route('projects.graph', $project))
            ->assertExactJson(['nodes' => [], 'links' => [], 'hyperedges' => [], 'relations' => []]);
        $this->postJson(route('projects.ask', $project), ['question' => 'Lanzamiento'])->assertUnprocessable();
    }

    public function test_project_graphs_and_their_event_streams_are_isolated_by_team_and_project(): void
    {
        $owner = User::factory()->withPersonalTeam()->create();
        $outsider = User::factory()->withPersonalTeam()->create();
        $team = $owner->personalTeam();
        $project = $team->projects()->create(['name' => 'Primero', 'objective' => 'Objetivo A']);
        $other = $team->projects()->create(['name' => 'Segundo', 'objective' => 'Objetivo B']);

        Redis::shouldReceive('connection->client->executeRaw')
            ->once()->withArgs(fn (array $args): bool => $args[0] === 'XADD' && $args[1] === $project->streamPrefix().':graph')
            ->andReturn('1-0');

        $graph = ['nodes' => [['id' => 'plan', 'community' => 0, 'community_name' => 'Entrega']], 'links' => []];
        $this->actingAs($owner)->from(route('projects.show', $project))
            ->post(route('projects.graph.import', $project), ['graph' => UploadedFile::fake()->createWithContent('graph.json', json_encode($graph))])
            ->assertRedirect(route('projects.show', $project))->assertSessionHasNoErrors();
        $this->getJson(route('projects.graph', $project))->assertJsonPath('nodes.0.name', 'Entrega');
        $this->getJson(route('projects.graph', $other))->assertJsonCount(0, 'nodes');

        $this->actingAs($outsider)->get(route('projects.show', $project))->assertForbidden();
        $this->getJson(route('projects.graph', $project))->assertForbidden();
        $this->getJson(route('projects.events', $project))->assertForbidden();
        $this->postJson(route('projects.ask', $project), ['question' => 'Entrega'])->assertForbidden();
    }

    public function test_only_team_administrators_can_replace_a_project_graph(): void
    {
        $owner = User::factory()->withPersonalTeam()->create();
        $editor = User::factory()->create();
        $admin = User::factory()->create();
        $team = $owner->personalTeam();
        $team->users()->attach($editor, ['role' => 'editor']);
        $team->users()->attach($admin, ['role' => 'admin']);
        $project = $team->projects()->create(['name' => 'Proyecto', 'objective' => 'Objetivo']);
        $graph = fn () => UploadedFile::fake()->createWithContent('graph.json', '{"nodes":[],"links":[]}');

        $this->getJson(route('projects.graph', $project))->assertUnauthorized();
        $this->actingAs($editor)->getJson(route('projects.graph', $project))->assertOk();
        $this->postJson(route('projects.graph.import', $project), ['graph' => $graph()])->assertForbidden();

        Redis::shouldReceive('connection->client->executeRaw')->once()->andReturn('1-0');
        $this->actingAs($admin)->post(route('projects.graph.import', $project), ['graph' => $graph()])
            ->assertRedirect()->assertSessionHasNoErrors();

        $team->users()->detach($admin);
        $this->actingAs($admin->fresh());
        $this->getJson(route('projects.events', $project))->assertForbidden();
        $this->postJson(route('projects.graph.import', $project), ['graph' => $graph()])->assertForbidden();
    }

    public function test_system_sphere_mutations_require_full_admin(): void
    {
        $this->postJson('/api/sphere/color', ['color' => '#00ffff'])->assertUnauthorized();
        $user = User::factory()->create();
        $this->actingAs($user)->postJson('/api/sphere/color', ['color' => '#00ffff'])->assertForbidden();
        $this->postJson('/api/sphere/direction', ['direction' => 1])->assertForbidden();
        $this->postJson('/api/sphere/community', ['community' => null])->assertForbidden();

        $user->assignRole(Role::findOrCreate('full admin', 'web'));
        Redis::shouldReceive('connection->client->executeRaw')->once()->andReturn('1-0');
        $this->postJson('/api/sphere/color', ['color' => '#00ffff'])->assertOk();
    }
}
