<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Rate;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProjectValidationTest extends TestCase
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

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Website',
            'client_id' => Client::create(['name' => 'Acme'])->id,
            'rate_id' => Rate::create(['description' => 'Standard', 'amount' => 140])->id,
            'is_collection' => false,
        ], $overrides);
    }

    public function test_a_flat_rate_project_needs_a_budget(): void
    {
        $this->postJson('/api/project/create', $this->payload())
            ->assertStatus(422)
            // The SPA maps this structure back onto the fields, so its shape matters.
            ->assertJsonValidationErrors(['budget' => 'A budget is required for flat-rate projects.']);
    }

    public function test_a_flat_rate_budget_must_be_greater_than_zero(): void
    {
        $this->postJson('/api/project/create', $this->payload(['budget' => 0]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['budget' => 'A budget is required for flat-rate projects.']);
    }

    public function test_a_collection_project_may_omit_the_budget(): void
    {
        $this->postJson('/api/project/create', $this->payload(['is_collection' => true]))
            ->assertOk();
    }
}
