<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceState;
use App\Models\LiquiditySnapshot;
use App\Models\Project;
use App\Models\Rate;
use App\Models\TimeEntry;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LiquidityTest extends TestCase
{
    use RefreshDatabase;

    private Client $client;
    private Rate $rate;

    protected function setUp(): void
    {
        parent::setUp();
        Sanctum::actingAs(User::forceCreate([
            'name' => 'Test',
            'email' => 'test@example.com',
            'password' => bcrypt('secret'),
        ]));

        foreach ([1 => 'Draft', 2 => 'Pending', 3 => 'Paid'] as $id => $description) {
            InvoiceState::forceCreate(['id' => $id, 'description' => $description]);
        }
        $this->client = Client::create(['name' => 'Acme']);
        $this->rate = Rate::create(['description' => 'Standard', 'amount' => 200]);
    }

    private function invoice(int $stateId, float $grandtotal): Invoice
    {
        return Invoice::create([
            'title' => 'Invoice',
            'date' => '2026-09-01',
            'date_due' => '2026-09-30',
            'client_id' => $this->client->id,
            'state_id' => $stateId,
            'vat_rate' => 8.1,
            'total' => $grandtotal,
            'vat' => 0,
            'grandtotal' => $grandtotal,
        ]);
    }

    private function project(array $attributes): Project
    {
        return Project::create(array_merge([
            'name' => 'Project',
            'rate_id' => $this->rate->id,
            'client_id' => $this->client->id,
        ], $attributes));
    }

    public function test_sources_list_only_pending_invoices(): void
    {
        $pending = $this->invoice(InvoiceState::PENDING, 1081);
        $this->invoice(InvoiceState::DRAFT, 500);
        $this->invoice(InvoiceState::PAID, 700);

        $res = $this->getJson('/api/liquidity/sources');

        $res->assertOk()
            ->assertJsonCount(1, 'invoices')
            ->assertJsonPath('invoices.0.id', $pending->id)
            ->assertJsonPath('invoices.0.amount', 1081);
    }

    public function test_sources_value_fixed_projects_at_budget_and_collections_at_unbilled(): void
    {
        $fixed = $this->project(['name' => 'Fixed', 'budget' => 5000, 'is_collection' => 0]);
        $collection = $this->project(['name' => 'Collection', 'is_collection' => 1]);
        $this->project(['name' => 'Archived', 'budget' => 3000, 'is_collection' => 0, 'is_archive' => 1]);
        $this->project(['name' => 'Empty collection', 'is_collection' => 1]);

        TimeEntry::create([
            'project_id' => $collection->id,
            'is_billable' => true,
            'date' => '2026-10-01',
            'time_from' => '08:00',
            'time_to' => '10:00',
            'hours' => 2,
        ]);

        $res = $this->getJson('/api/liquidity/sources');

        $projects = collect($res->assertOk()->json('projects'))->keyBy('id');
        $this->assertCount(2, $projects);
        $this->assertEquals(5000, $projects[$fixed->id]['amount']);
        $this->assertEquals(400, $projects[$collection->id]['amount']);
    }

    public function test_stores_and_updates_a_snapshot(): void
    {
        $payload = [
            'name' => 'October',
            'data' => [
                'balance' => 42350.5,
                'items' => [
                    ['label' => 'VAT Q3', 'amount' => -6200, 'date' => '2026-10-31'],
                    ['label' => 'Refund', 'amount' => 180],
                ],
                'invoice_ids' => [3, 3, 4],
                'project_ids' => [7],
            ],
        ];

        $id = $this->postJson('/api/liquidity/snapshot/create', $payload)->assertOk()->json('id');

        $data = LiquiditySnapshot::find($id)->data;
        $this->assertEquals(42350.5, $data['balance']);
        $this->assertEquals([3, 4], $data['invoice_ids']);
        $this->assertNull($data['items'][1]['date']);

        $payload['name'] = 'October revised';
        $this->postJson("/api/liquidity/snapshot/update/{$id}", $payload)->assertOk();
        $this->assertEquals('October revised', LiquiditySnapshot::find($id)->name);

        $this->getJson('/api/liquidity/snapshots/get')->assertOk()->assertJsonCount(1);
        $this->deleteJson("/api/liquidity/snapshot/destroy/{$id}")->assertOk();
        $this->assertNull(LiquiditySnapshot::find($id));
    }

    public function test_snapshot_requires_a_name_and_labelled_items(): void
    {
        $this->postJson('/api/liquidity/snapshot/create', [
            'data' => ['items' => [['amount' => 10]]],
        ])->assertStatus(422)->assertJsonValidationErrors(['name', 'data.items.0.label']);
    }
}
