<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GraphNodeDetailsTest extends TestCase
{
    use RefreshDatabase;

    public function test_node_details_require_access_to_the_project_and_exact_node_id(): void
    {
        $owner = User::factory()->withPersonalTeam()->create();
        $outsider = User::factory()->withPersonalTeam()->create();
        $project = $owner->personalTeam()->projects()->create([
            'name' => 'Private graph', 'objective' => 'Test',
            'graph' => ['nodes' => [
                ['id' => 'User/a', 'label' => 'User', 'description' => 'Account management', 'source_file' => 'app/User.php'],
                ['id' => 'Team', 'label' => 'Team'],
            ], 'links' => [['source' => 'User/a', 'target' => 'Team', 'relation' => 'belongs_to']]],
        ]);
        $url = route('projects.node', $project).'?'.http_build_query(['id' => 'User/a']);
        $this->getJson($url)->assertUnauthorized();
        $this->actingAs($outsider)->getJson($url)->assertForbidden();
        $this->actingAs($owner)->getJson($url)->assertOk()
            ->assertJsonPath('node.id', 'User/a')
            ->assertJsonPath('node.description', 'Account management')
            ->assertJsonPath('node.source_file', 'app/User.php')
            ->assertJsonPath('relations.0.relation', 'belongs_to')
            ->assertJsonPath('neighbors.0.label', 'Team');
        $this->getJson(route('projects.node', $project).'?id=unknown')->assertNotFound();
        $this->getJson(route('projects.node', $project))->assertUnprocessable();
        $other = $owner->personalTeam()->projects()->create(['name' => 'Empty graph', 'objective' => 'Test']);
        $this->getJson(route('projects.node', $other).'?id=Team')->assertNotFound();
        $this->getJson('/api/sphere/node?id=Team')->assertForbidden();
    }
}
