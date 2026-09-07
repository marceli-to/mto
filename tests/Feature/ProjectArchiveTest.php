<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Project;
use App\Models\Rate;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProjectArchiveTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Sanctum::actingAs(User::forceCreate([
            'name' => 'Test',
            'email' => 'test@example.com',
            'password' => bcrypt('secret'),
        ]));
    }

    private function project(int $isArchive = 0): Project
    {
        $rate = Rate::create(['description' => 'Standard', 'amount' => 200]);
        $client = Client::create(['name' => 'Acme']);

        return Project::create([
            'name' => 'Website',
            'rate_id' => $rate->id,
            'client_id' => $client->id,
            'is_archive' => $isArchive,
        ]);
    }

    public function test_archives_an_active_project(): void
    {
        $project = $this->project();

        $res = $this->postJson("/api/project/archive/{$project->id}");

        $res->assertOk()->assertJsonPath('is_archive', 1);
        $this->assertEquals(1, $project->fresh()->is_archive);
    }

    public function test_restores_an_archived_project(): void
    {
        $project = $this->project(1);

        $res = $this->postJson("/api/project/archive/{$project->id}");

        $res->assertOk()->assertJsonPath('is_archive', 0);
        $this->assertEquals(0, $project->fresh()->is_archive);
    }

    public function test_returns_the_client_so_the_list_row_stays_complete(): void
    {
        $project = $this->project();

        $this->postJson("/api/project/archive/{$project->id}")
            ->assertOk()
            ->assertJsonPath('client.name', 'Acme');
    }

    public function test_archiving_keeps_the_project_in_the_list(): void
    {
        $project = $this->project();

        $this->postJson("/api/project/archive/{$project->id}")->assertOk();

        // Archiving is a state change, not a deletion — the list still returns it
        // and the frontend filters on is_archive.
        $this->getJson('/api/projects/get')
            ->assertOk()
            ->assertJsonPath('data.0.id', $project->id)
            ->assertJsonPath('data.0.is_archive', 1);
    }
}
