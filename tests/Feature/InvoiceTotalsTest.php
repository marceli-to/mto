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

class InvoiceTotalsTest extends TestCase
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

        if (!InvoiceState::find(1)) {
            InvoiceState::forceCreate(['id' => 1, 'description' => 'open']);
        }
    }

    private function collectionProject(int $isArchive = 0): Project
    {
        $rate = Rate::create(['description' => 'Standard', 'amount' => 200]);
        $client = Client::create(['name' => 'Acme']);

        return Project::create([
            'name' => 'Website',
            'rate_id' => $rate->id,
            'client_id' => $client->id,
            'is_collection' => true,
            'is_archive' => $isArchive,
        ]);
    }

    private function invoice(): Invoice
    {
        $client = Client::create(['name' => 'Bill Co']);

        return Invoice::create([
            'title' => 'Test Invoice',
            'date' => '2026-09-01',
            'date_due' => '2026-09-30',
            'client_id' => $client->id,
            'state_id' => 1,
            'vat_rate' => 8.1,
            'total' => 0,
            'vat' => 0,
            'grandtotal' => 0,
        ]);
    }

    private function unbilledHours(Project $project, float $hours): TimeEntry
    {
        return TimeEntry::create([
            'project_id' => $project->id,
            'date' => '2026-09-05',
            'hours' => $hours,
            'is_billable' => true,
            'description' => 'Work',
        ]);
    }

    public function test_counts_unbilled_work_on_active_projects(): void
    {
        $this->unbilledHours($this->collectionProject(), 3);

        $this->getJson('/api/invoices/get')
            ->assertOk()
            ->assertJsonPath('totals.unbilled', 600);
    }

    public function test_excludes_unbilled_work_on_archived_projects(): void
    {
        $this->unbilledHours($this->collectionProject(1), 3);

        $this->getJson('/api/invoices/get')
            ->assertOk()
            ->assertJsonPath('totals.unbilled', 0);
    }

    public function test_project_list_reports_unbilled_count_and_value(): void
    {
        $project = $this->collectionProject();
        $this->unbilledHours($project, 3);
        $this->unbilledHours($project, 1);

        $this->getJson('/api/projects/get')
            ->assertOk()
            ->assertJsonPath('data.0.unbilled_count', 2)
            ->assertJsonPath('data.0.unbilled_value', 800);
    }

    public function test_billed_entries_are_left_out_of_the_project_unbilled_figure(): void
    {
        $project = $this->collectionProject();
        $this->unbilledHours($project, 3);
        $billed = $this->unbilledHours($project, 1);
        $billed->update(['invoice_id' => $this->invoice()->id]);

        $this->getJson('/api/projects/get')
            ->assertOk()
            ->assertJsonPath('data.0.unbilled_count', 1)
            ->assertJsonPath('data.0.unbilled_value', 600);
    }

    public function test_project_with_nothing_unbilled_reports_zero(): void
    {
        $this->collectionProject();

        $this->getJson('/api/projects/get')
            ->assertOk()
            ->assertJsonPath('data.0.unbilled_count', 0)
            ->assertJsonPath('data.0.unbilled_value', 0);
    }

    public function test_archiving_a_project_drops_its_work_from_the_open_figure(): void
    {
        $project = $this->collectionProject();
        $this->unbilledHours($project, 3);

        $before = $this->getJson('/api/invoices/get')->json('totals.open');

        $this->postJson("/api/project/archive/{$project->id}")->assertOk();

        $after = $this->getJson('/api/invoices/get')->json('totals.open');

        $this->assertEquals(600, $before - $after);
    }
}
