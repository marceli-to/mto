<?php

namespace Tests\Unit;

use App\Actions\TimeEntry\RevenueEngine;
use App\Models\TimeEntry;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Tests\TestCase;

class RevenueEngineTest extends TestCase
{
    /**
     * Build a non-persisted TimeEntry with a fixed resolved rate.
     * We set the `rate` override so resolvedRate() doesn't need a loaded project.
     */
    private function entry(int $id, int $projectId, string $date, float $hours, float $rate, bool $billable = true): TimeEntry
    {
        $e = new TimeEntry([
            'project_id'  => $projectId,
            'date'        => $date,
            'hours'       => $hours,
            'rate'        => $rate,
            'is_billable' => $billable,
        ]);
        $e->id = $id;
        return $e;
    }

    private function activity(int $id, string $date, float $hours): TimeEntry
    {
        $e = new TimeEntry([
            'project_id'  => null,
            'activity'    => 'Gym',
            'date'        => $date,
            'hours'       => $hours,
            'is_billable' => false,
        ]);
        $e->id = $id;
        return $e;
    }

    /** Worked example: budget 5000, rate 200/h, 20h / 10h / 5h across three weeks -> 4000 / 1000 / 0. */
    public function test_cumulative_budget_cap_worked_example(): void
    {
        $entries = new Collection([
            $this->entry(1, 1, '2026-09-01', 20, 200), // value 4000
            $this->entry(2, 1, '2026-09-08', 10, 200), // value 2000
            $this->entry(3, 1, '2026-09-15', 5, 200),  // value 1000
        ]);

        $engine = new RevenueEngine($entries, [1 => 5000.0]);
        $per = $engine->perEntryRevenue();

        $this->assertSame(4000.0, $per[1]['revenue']);
        $this->assertSame(0.0, $per[1]['over_budget']);

        $this->assertSame(1000.0, $per[2]['revenue']);
        $this->assertSame(1000.0, $per[2]['over_budget']);

        $this->assertSame(0.0, $per[3]['revenue']);
        $this->assertSame(1000.0, $per[3]['over_budget']);
    }

    /** Collection project with null budget = uncapped: all value is revenue. */
    public function test_uncapped_project_all_value_is_revenue(): void
    {
        $entries = new Collection([
            $this->entry(1, 7, '2026-09-01', 50, 200), // value 10000
        ]);

        $engine = new RevenueEngine($entries, [7 => null]);
        $per = $engine->perEntryRevenue();

        $this->assertSame(10000.0, $per[1]['revenue']);
        $this->assertSame(0.0, $per[1]['over_budget']);
    }

    /** An entry straddling the budget boundary is split into revenue + over-budget. */
    public function test_straddling_entry_is_split(): void
    {
        // budget 1000, single 10h @ 200 = 2000 -> 1000 revenue, 1000 over.
        $entries = new Collection([
            $this->entry(1, 2, '2026-09-01', 10, 200),
        ]);

        $engine = new RevenueEngine($entries, [2 => 1000.0]);
        $per = $engine->perEntryRevenue();

        $this->assertSame(1000.0, $per[1]['revenue']);
        $this->assertSame(1000.0, $per[1]['over_budget']);
    }

    /** Non-billable project entries and activity entries are excluded and consume no budget. */
    public function test_non_billable_and_activity_excluded(): void
    {
        $entries = new Collection([
            $this->entry(1, 1, '2026-09-01', 5, 200, billable: false), // non-billable -> excluded
            $this->activity(2, '2026-09-01', 2),                        // activity -> excluded
            $this->entry(3, 1, '2026-09-02', 10, 200),                  // billable value 2000
        ]);

        $engine = new RevenueEngine($entries, [1 => 5000.0]);
        $per = $engine->perEntryRevenue();

        $this->assertArrayNotHasKey(1, $per);
        $this->assertArrayNotHasKey(2, $per);
        // The non-billable 5h did NOT consume budget, so the billable 10h is fully revenue.
        $this->assertSame(2000.0, $per[3]['revenue']);
        $this->assertSame(0.0, $per[3]['over_budget']);
    }

    /** periodRevenue attributes revenue to the period the entry is dated in. */
    public function test_period_revenue_reflects_cumulative_state(): void
    {
        $entries = new Collection([
            $this->entry(1, 1, '2026-09-01', 20, 200), // week 1: 4000 revenue
            $this->entry(2, 1, '2026-09-08', 10, 200), // week 2: 1000 revenue (1000 over)
        ]);

        $engine = new RevenueEngine($entries, [1 => 5000.0]);

        $week1 = $engine->periodRevenue(Carbon::parse('2026-08-31'), Carbon::parse('2026-09-06'));
        $week2 = $engine->periodRevenue(Carbon::parse('2026-09-07'), Carbon::parse('2026-09-13'));

        $this->assertSame(4000.0, $week1);
        $this->assertSame(1000.0, $week2);
    }
}
