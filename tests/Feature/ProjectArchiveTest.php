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

    private function invoice(int $clientId): Invoice
    {
        // invoices.state_id defaults to 1 with a FK to states — ensure it exists.
        if (!InvoiceState::find(1)) {
            InvoiceState::forceCreate(['id' => 1, 'description' => 'Draft']);
        }

        return Invoice::create([
            'title' => 'Handwritten invoice',
            'date' => '2026-09-01',
            'date_due' => '2026-09-30',
            'client_id' => $clientId,
            'state_id' => 1,
            'vat_rate' => 8.1,
            'total' => 0,
            'vat' => 0,
            'grandtotal' => 0,
        ]);
    }

    private function entry(Project $project, array $attrs = []): TimeEntry
    {
        return TimeEntry::create($attrs + [
            'project_id' => $project->id,
            'date' => '2026-09-05',
            'hours' => 1,
            'is_billable' => true,
        ]);
    }

    public function test_archiving_with_an_invoice_marks_unbilled_entries_as_billed(): void
    {
        $project = $this->project();
        $invoice = $this->invoice($project->client_id);
        $earlier = $this->invoice($project->client_id);

        $unbilled = $this->entry($project);
        $alreadyBilled = $this->entry($project, ['invoice_id' => $earlier->id]);
        $nonBillable = $this->entry($project, ['is_billable' => false]);

        $this->postJson("/api/project/archive/{$project->id}", ['invoice_id' => $invoice->id])
            ->assertOk()
            ->assertJsonPath('is_archive', 1)
            ->assertJsonPath('unbilled_count', 0);

        $this->assertSame($invoice->id, $unbilled->fresh()->invoice_id);
        $this->assertSame($earlier->id, $alreadyBilled->fresh()->invoice_id);
        $this->assertNull($nonBillable->fresh()->invoice_id);
    }

    public function test_archiving_without_an_invoice_leaves_entries_unbilled(): void
    {
        $project = $this->project();
        $entry = $this->entry($project);

        $this->postJson("/api/project/archive/{$project->id}")->assertOk();

        $this->assertNull($entry->fresh()->invoice_id);
    }

    public function test_restoring_ignores_an_invoice(): void
    {
        $project = $this->project(1);
        $invoice = $this->invoice($project->client_id);
        $entry = $this->entry($project);

        $this->postJson("/api/project/archive/{$project->id}", ['invoice_id' => $invoice->id])
            ->assertOk()
            ->assertJsonPath('is_archive', 0);

        $this->assertNull($entry->fresh()->invoice_id);
    }

    public function test_rejects_an_unknown_invoice(): void
    {
        $project = $this->project();

        $this->postJson("/api/project/archive/{$project->id}", ['invoice_id' => 9999])
            ->assertStatus(422);

        $this->assertEquals(0, $project->fresh()->is_archive);
    }
}
