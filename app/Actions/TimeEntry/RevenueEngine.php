<?php

namespace App\Actions\TimeEntry;

use App\Models\TimeEntry;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Computes budget-capped revenue from time entries.
 *
 * Rule: for each project, entries consume the project's budget cumulatively,
 * oldest-entry-first (ties broken by id). An entry's value = hours * resolvedRate.
 * The portion of that value fitting under the remaining budget is revenue; the rest
 * is over-budget (not revenue). Budget caps in both project modes. A collection
 * project with null/0 budget is uncapped (all value is revenue).
 *
 * Only BILLABLE PROJECT entries participate. Activity entries and non-billable
 * project entries contribute 0 and never consume budget.
 */
class RevenueEngine
{
    /**
     * @param  Collection<int,TimeEntry>  $entries  All entries in scope (any kind).
     * @param  array<int,float|null>      $budgets  project_id => budget (null = uncapped).
     */
    public function __construct(
        protected Collection $entries,
        protected array $budgets = []
    ) {}

    /**
     * Build an engine from the database for a set of projects (or all projects).
     *
     * Loads ALL billable project entries across time (needed for correct cumulative
     * budget consumption), ordered oldest-first.
     *
     * @param  array<int>|null  $projectIds  Restrict to these projects, or null for all.
     */
    public static function fromDatabase(?array $projectIds = null): self
    {
        $query = TimeEntry::query()
            ->billable()
            ->with('project.rateModel')
            ->orderBy('project_id')
            ->orderBy('date')
            ->orderBy('id');

        if (!is_null($projectIds)) {
            $query->whereIn('project_id', $projectIds);
        }

        $entries = $query->get();

        $budgets = $entries
            ->pluck('project')
            ->filter()
            ->unique('id')
            ->mapWithKeys(fn ($p) => [$p->id => self::normalizeBudget($p->budget)])
            ->all();

        return new self($entries, $budgets);
    }

    /**
     * Per-entry breakdown keyed by entry id:
     *   [id => ['value' => float, 'revenue' => float, 'over_budget' => float]]
     *
     * Only billable project entries appear with non-zero figures; other entries
     * (if present in the collection) resolve to zeros.
     */
    public function perEntryRevenue(): array
    {
        $result = [];

        // Group billable project entries by project, ordered oldest-first.
        $byProject = $this->entries
            ->filter(fn (TimeEntry $e) => $e->is_billable && !is_null($e->project_id))
            ->sortBy([['date', 'asc'], ['id', 'asc']])
            ->groupBy('project_id');

        foreach ($byProject as $projectId => $projectEntries) {
            $budget   = $this->budgets[$projectId] ?? null; // null = uncapped
            $consumed = 0.0;

            foreach ($projectEntries as $entry) {
                $value = $entry->value();

                if (is_null($budget)) {
                    $revenue = $value;
                } else {
                    $remaining = max(0.0, $budget - $consumed);
                    $revenue   = min($value, $remaining);
                }

                $result[$entry->id] = [
                    'value'       => round($value, 2),
                    'revenue'     => round($revenue, 2),
                    'over_budget' => round($value - $revenue, 2),
                ];

                $consumed += $value; // track full value so later entries see true consumption
            }
        }

        return $result;
    }

    /**
     * Revenue attributable to entries dated within [from, to] (inclusive),
     * given budget already consumed by all earlier entries.
     */
    public function periodRevenue(Carbon $from, Carbon $to): float
    {
        $perEntry = $this->perEntryRevenue();
        $total = 0.0;

        foreach ($this->entries as $entry) {
            if (!isset($perEntry[$entry->id])) {
                continue;
            }
            $date = $entry->date instanceof Carbon ? $entry->date : Carbon::parse($entry->date);
            if ($date->betweenIncluded($from->copy()->startOfDay(), $to->copy()->endOfDay())) {
                $total += $perEntry[$entry->id]['revenue'];
            }
        }

        return round($total, 2);
    }

    /** null/0 => uncapped (null); otherwise the numeric budget. */
    protected static function normalizeBudget($budget): ?float
    {
        if (is_null($budget)) {
            return null;
        }
        $budget = (float) $budget;
        return $budget > 0 ? $budget : null;
    }
}
