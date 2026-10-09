<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceState;
use App\Models\Project;
use App\Models\Rate;
use App\Models\TimeEntry;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TimeEntryDescriptionTest extends TestCase
{
    use RefreshDatabase;

    private TimeEntry $entry;

    protected function setUp(): void
    {
        parent::setUp();
        Sanctum::actingAs(User::forceCreate([
            'name' => 'Test',
            'email' => 'test@example.com',
            'password' => bcrypt('secret'),
        ]));

        $rate = Rate::create(['description' => 'Standard', 'amount' => 140]);
        $client = Client::create(['name' => 'Acme']);
        $project = Project::create([
            'name' => 'Collection',
            'rate_id' => $rate->id,
            'client_id' => $client->id,
            'is_collection' => 1,
        ]);

        $this->entry = TimeEntry::create([
            'project_id' => $project->id,
            'is_billable' => true,
            'date' => '2026-10-08',
            'time_from' => '08:00',
            'time_to' => '08:15',
            'hours' => 0.25,
            'description' => 'Old',
        ]);
    }

    public function test_updates_only_the_description(): void
    {
        $this->postJson("/api/time-entry/description/{$this->entry->id}", ['description' => 'New'])
            ->assertOk();

        $fresh = $this->entry->fresh();
        $this->assertEquals('New', $fresh->description);
        $this->assertEquals(0.25, (float) $fresh->hours);
        $this->assertEquals('08:00', substr($fresh->time_from, 0, 5));
    }

    public function test_clears_the_description(): void
    {
        $this->postJson("/api/time-entry/description/{$this->entry->id}", ['description' => null])
            ->assertOk();

        $this->assertNull($this->entry->fresh()->description);
    }

    public function test_refuses_billed_entries(): void
    {
        InvoiceState::forceCreate(['id' => 1, 'description' => 'Draft']);
        $invoice = Invoice::create([
            'title' => 'Invoice',
            'date' => '2026-10-09',
            'date_due' => '2026-11-08',
            'client_id' => $this->entry->project->client_id,
            'state_id' => 1,
            'vat_rate' => 8.1,
            'total' => 0,
            'vat' => 0,
            'grandtotal' => 0,
        ]);
        $this->entry->update(['invoice_id' => $invoice->id]);

        $this->postJson("/api/time-entry/description/{$this->entry->id}", ['description' => 'New'])
            ->assertStatus(422);

        $this->assertEquals('Old', $this->entry->fresh()->description);
    }
}
