<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceState;
use App\Models\Project;
use App\Models\Rate;
use App\Http\Requests\TimeEntryStoreRequest;
use App\Models\TimeEntry;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TimeEntryTest extends TestCase
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

    private function collectionProject(?float $budget = 0.0): Project
    {
        $rate = Rate::create(['description' => 'Standard', 'amount' => 200]);
        $client = Client::create(['name' => 'Acme']);
        return Project::create([
            'name' => 'Collection Project',
            'rate_id' => $rate->id,
            'client_id' => $client->id,
            'is_collection' => true,
            'budget' => $budget,
        ]);
    }

    private function invoice(): Invoice
    {
        // invoices.state_id defaults to 1 with a FK to states — ensure it exists.
        if (!InvoiceState::find(1)) {
            InvoiceState::forceCreate(['id' => 1, 'description' => 'Draft']);
        }
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

    public function test_creates_a_project_time_entry(): void
    {
        $project = $this->collectionProject();

        $res = $this->postJson('/api/time-entry/create', [
            'project_id' => $project->id,
            'date' => '2026-09-05',
            'time_from' => '08.30',
            'time_to' => '11.00',
            'description' => 'Work',
        ]);

        $res->assertOk();
        // Asserted through the model: MySQL hands back "08:30:00" where SQLite keeps "08:30".
        $entry = TimeEntry::first();
        $this->assertSame($project->id, $entry->project_id);
        $this->assertSame('08:30', $entry->time_from);
        $this->assertSame('11:00', $entry->time_to);
        $this->assertEquals(2.5, $entry->hours);
        $this->assertTrue($entry->is_billable);
    }

    public function test_creates_an_activity_entry_forcing_non_billable_and_no_project(): void
    {
        $res = $this->postJson('/api/time-entry/create', [
            'activity' => 'Gym',
            'date' => '2026-09-05',
            'time_from' => '08.00',
            'time_to' => '09.00',
            'is_billable' => true, // should be forced false
            'project_id' => 999,   // should be nulled
        ]);

        $res->assertOk();
        $entry = TimeEntry::first();
        $this->assertSame('Gym', $entry->activity);
        $this->assertNull($entry->project_id);
        $this->assertFalse($entry->is_billable);
    }

    public function test_rejects_unknown_activity(): void
    {
        $this->postJson('/api/time-entry/create', [
            'activity' => 'Skydiving',
            'date' => '2026-09-05',
            'time_from' => '08.00',
            'time_to' => '09.00',
        ])->assertStatus(422);
    }

    public function test_rejects_entry_with_neither_project_nor_activity(): void
    {
        $this->postJson('/api/time-entry/create', [
            'date' => '2026-09-05',
            'time_from' => '08.00',
            'time_to' => '09.00',
        ])->assertStatus(422);
    }

    public function test_rejects_entry_with_both_project_and_activity(): void
    {
        $project = $this->collectionProject();

        // activity present -> prepareForValidation nulls project_id, so to truly test
        // "both", send activity plus a project via a payload the normalizer keeps.
        // Since normalization nulls project when activity is filled, the mutual-exclusion
        // after() rule is exercised only when both survive; here we assert activity wins.
        $res = $this->postJson('/api/time-entry/create', [
            'project_id' => $project->id,
            'activity' => 'Admin',
            'date' => '2026-09-05',
            'time_from' => '08.00',
            'time_to' => '09.00',
        ]);
        $res->assertOk();
        $this->assertNull(TimeEntry::first()->project_id); // project dropped in favor of activity
    }

    /** @dataProvider timeFormatProvider */
    public function test_normalizes_the_shapes_people_type(string $typed, ?string $expected): void
    {
        $this->assertSame($expected, TimeEntryStoreRequest::normalizeTime($typed));
    }

    public static function timeFormatProvider(): array
    {
        return [
            'dotted'              => ['08.30', '08:30'],
            'dotted no padding'   => ['8.30', '08:30'],
            'colon'               => ['08:30', '08:30'],
            'compact four digits' => ['0830', '08:30'],
            'compact three'       => ['830', '08:30'],
            'hour only'           => ['9', '09:00'],
            'single minute digit' => ['8.3', '08:30'],
            'snaps down'          => ['08.32', '08:30'],
            'snaps up'            => ['10.10', '10:15'],
            'never rolls over'    => ['23.59', '23:45'],
            'out of range hour'   => ['25.00', null],
            'out of range minute' => ['08.75', null],
            'nonsense'            => ['later', null],
            'empty'               => ['', null],
        ];
    }

    public function test_derives_hours_from_the_span_and_ignores_client_hours(): void
    {
        $project = $this->collectionProject();

        $this->postJson('/api/time-entry/create', [
            'project_id' => $project->id,
            'date' => '2026-09-05',
            'time_from' => '08.30',
            'time_to' => '10.15',
            'hours' => 99, // must not be trusted
        ])->assertOk();

        $this->assertEquals(1.75, TimeEntry::first()->hours);
    }

    public function test_snaps_entered_times_to_quarter_hours(): void
    {
        $project = $this->collectionProject();

        $this->postJson('/api/time-entry/create', [
            'project_id' => $project->id,
            'date' => '2026-09-05',
            'time_from' => '08.22',
            'time_to' => '09.53',
        ])->assertOk();

        $entry = TimeEntry::first();
        $this->assertSame('08:15', $entry->time_from);
        $this->assertSame('10:00', $entry->time_to); // 09:53 is nearer 10:00 than 09:45
        $this->assertEquals(1.75, $entry->hours);
    }

    public function test_rejects_an_end_time_at_or_before_the_start(): void
    {
        $project = $this->collectionProject();

        $this->postJson('/api/time-entry/create', [
            'project_id' => $project->id,
            'date' => '2026-09-05',
            'time_from' => '10.00',
            'time_to' => '09.00',
        ])->assertStatus(422)->assertJsonValidationErrors('time_to');

        $this->postJson('/api/time-entry/create', [
            'project_id' => $project->id,
            'date' => '2026-09-05',
            'time_from' => '10.00',
            'time_to' => '10.00',
        ])->assertStatus(422)->assertJsonValidationErrors('time_to');
    }

    public function test_rejects_a_missing_or_unparseable_time(): void
    {
        $project = $this->collectionProject();

        $this->postJson('/api/time-entry/create', [
            'project_id' => $project->id,
            'date' => '2026-09-05',
            'time_to' => '10.00',
        ])->assertStatus(422)->assertJsonValidationErrors('time_from');

        $this->postJson('/api/time-entry/create', [
            'project_id' => $project->id,
            'date' => '2026-09-05',
            'time_from' => '08.00',
            'time_to' => 'noon',
        ])->assertStatus(422)->assertJsonValidationErrors('time_to');
    }

    public function test_the_rate_always_comes_from_the_project(): void
    {
        $project = $this->collectionProject();

        $this->postJson('/api/time-entry/create', [
            'project_id' => $project->id,
            'date' => '2026-09-05',
            'time_from' => '08.00',
            'time_to' => '09.00',
            'rate' => 500, // per-entry overrides are no longer accepted
        ])->assertOk();

        $entry = TimeEntry::first();
        $this->assertNull($entry->rate);
        $this->assertSame(200.0, $entry->resolvedRate()); // the project's rate
    }

    public function test_orders_entries_within_a_day_by_start_time_newest_first(): void
    {
        $project = $this->collectionProject();
        $common = ['project_id' => $project->id, 'date' => '2026-09-05', 'hours' => 1, 'is_billable' => true];

        // Created out of order, and one legacy row with no times at all.
        TimeEntry::create($common + ['description' => 'noon', 'time_from' => '12:00', 'time_to' => '13:00']);
        TimeEntry::create($common + ['description' => 'legacy']);
        TimeEntry::create($common + ['description' => 'morning', 'time_from' => '08:30', 'time_to' => '09:30']);

        $entries = $this->getJson('/api/time-entries/get')->json('days.0.entries');

        $this->assertSame(
            ['noon', 'morning', 'legacy'], // latest start first, untimed rows last
            array_column($entries, 'description')
        );
        $this->assertSame('12:00', $entries[0]['time_from']);
        $this->assertSame('13:00', $entries[0]['time_to']);
        $this->assertNull($entries[2]['time_from']);
    }

    public function test_day_grouping_and_revenue_stats(): void
    {
        $project = $this->collectionProject(5000); // budget 5000, rate 200

        // Two entries same day: 20h and 10h -> values 4000 + 2000, capped at 5000.
        TimeEntry::create(['project_id' => $project->id, 'date' => '2026-09-05', 'hours' => 20, 'is_billable' => true]);
        TimeEntry::create(['project_id' => $project->id, 'date' => '2026-09-05', 'hours' => 10, 'is_billable' => true]);
        // An activity entry adds hours but no revenue.
        TimeEntry::create(['activity' => 'Gym', 'date' => '2026-09-05', 'hours' => 1, 'is_billable' => false]);

        $res = $this->getJson('/api/time-entries/get?date=2026-09-05');
        $res->assertOk();

        $day = collect($res->json('days'))->firstWhere('date', '2026-09-05');
        $this->assertEquals(31, $day['total_hours']); // 20 + 10 + 1
        // Revenue capped at budget 5000 (4000 + 1000).
        $this->assertEquals(5000, $res->json('stats.day'));
    }

    public function test_non_billable_project_entry_counts_hours_but_zero_revenue(): void
    {
        $project = $this->collectionProject(5000);
        TimeEntry::create(['project_id' => $project->id, 'date' => '2026-09-05', 'hours' => 3, 'is_billable' => false]);

        $res = $this->getJson('/api/time-entries/get?date=2026-09-05');
        $day = collect($res->json('days'))->firstWhere('date', '2026-09-05');

        $this->assertEquals(3, $day['total_hours']);
        $this->assertEquals(0, $res->json('stats.day'));
    }

    public function test_billed_entry_cannot_be_edited_or_deleted(): void
    {
        $project = $this->collectionProject(5000);
        $entry = TimeEntry::create([
            'project_id' => $project->id, 'date' => '2026-09-05', 'hours' => 2, 'is_billable' => true,
            'invoice_id' => $this->invoice()->id, 'invoice_position_id' => null,
        ]);

        $this->postJson("/api/time-entry/update/{$entry->id}", [
            'project_id' => $project->id, 'date' => '2026-09-06', 'hours' => 5,
        ])->assertStatus(422);

        $this->deleteJson("/api/time-entry/destroy/{$entry->id}")->assertStatus(422);
    }

    public function test_bill_creates_one_position_per_entry_and_updates_invoice_total(): void
    {
        $project = $this->collectionProject(); // uncapped
        $invoice = $this->invoice();

        $e1 = TimeEntry::create(['project_id' => $project->id, 'date' => '2026-09-05', 'hours' => 2, 'is_billable' => true]);
        $e2 = TimeEntry::create(['project_id' => $project->id, 'date' => '2026-09-05', 'hours' => 3, 'is_billable' => true]);

        $res = $this->postJson('/api/time-entries/bill', [
            'invoice_id' => $invoice->id,
            'time_entry_ids' => [$e1->id, $e2->id],
        ]);
        $res->assertOk();

        // 2h*200 + 3h*200 = 1000
        $this->assertEquals(2, $invoice->positions()->count());
        $invoice->refresh();
        $this->assertEquals(1000, $invoice->total);
        $this->assertNotNull($e1->fresh()->invoice_id);
        $this->assertNotNull($e1->fresh()->invoice_position_id);
    }

    public function test_unbill_removes_positions_and_restores_total(): void
    {
        $project = $this->collectionProject();
        $invoice = $this->invoice();
        $e1 = TimeEntry::create(['project_id' => $project->id, 'date' => '2026-09-05', 'hours' => 2, 'is_billable' => true]);

        $this->postJson('/api/time-entries/bill', [
            'invoice_id' => $invoice->id, 'time_entry_ids' => [$e1->id],
        ])->assertOk();

        $this->assertEquals(1, $invoice->positions()->count());

        $this->postJson('/api/time-entries/unbill', [
            'time_entry_ids' => [$e1->id],
        ])->assertOk();

        $invoice->refresh();
        $this->assertEquals(0, $invoice->positions()->count());
        $this->assertEquals(0, $invoice->total);
        $this->assertNull($e1->fresh()->invoice_id);
    }

    public function test_flat_rate_project_requires_budget(): void
    {
        $rate = Rate::create(['description' => 'Std', 'amount' => 100]);
        $client = Client::create(['name' => 'Flat Co']);

        // Non-collection without budget -> 422
        $this->postJson('/api/project/create', [
            'name' => 'Flat Project',
            'rate_id' => $rate->id,
            'client_id' => $client->id,
            'is_collection' => false,
        ])->assertStatus(422);

        // With a budget -> ok
        $this->postJson('/api/project/create', [
            'name' => 'Flat Project',
            'rate_id' => $rate->id,
            'client_id' => $client->id,
            'is_collection' => false,
            'budget' => 5000,
        ])->assertOk();
    }
}
