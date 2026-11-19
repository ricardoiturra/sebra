<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\FullAdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardGraphTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_visualizer_requires_a_session(): void
    {
        $this->get('/')->assertOk()->assertDontSee('data-graph-url');
        $this->get('/dashboard')->assertRedirect(route('login'));
        $this->getJson('/api/sphere/graph')->assertUnauthorized();
        $this->getJson('/api/sphere/events')->assertUnauthorized();
    }

    public function test_users_without_full_admin_cannot_access_the_visualizer_or_graph_api(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $this->actingAs($user);

        $this->get('/dashboard')->assertOk()->assertDontSee('data-graph-url');
        $this->get('/projects/sebra-system')->assertForbidden();
        $this->getJson('/api/sphere/graph')->assertForbidden();
        $this->getJson('/api/sphere/events')->assertForbidden();
        $this->postJson('/api/graph/query', ['question' => 'Teams'])->assertForbidden();
        $this->postJson('/api/graph/questions', ['question' => 'Teams'])->assertForbidden();
        $this->postJson('/api/graph/ask', ['question' => 'Teams'])->assertForbidden();
    }

    public function test_full_admins_can_view_the_project_graph(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $user->assignRole('full admin');

        $this->actingAs($user)->get('/projects/sebra-system')
            ->assertOk()
            ->assertSee('data-graph-url="/api/sphere/graph"', false)
            ->assertSee('data-graph-events="/api/sphere/events"', false);
    }

    public function test_full_admin_seeder_creates_and_reuses_the_admin_account(): void
    {
        config(['auth.full_admin_seed_password' => 'Temporary-Test-Password-123']);

        $this->seed(FullAdminSeeder::class);
        $user = User::where('email', 'admin@er28-0652.com')->firstOrFail();
        $this->assertTrue($user->hasRole('full admin'));
        $this->assertNotNull($user->personalTeam());
        $passwordHash = $user->password;

        config(['auth.full_admin_seed_password' => null]);
        $this->seed(FullAdminSeeder::class);
        $this->assertSame($passwordHash, $user->fresh()->password);
        $this->assertSame(1, User::where('email', $user->email)->count());
    }
}
