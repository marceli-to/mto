<?php

namespace App\Actions\TimeEntry;

use App\Models\TimeEntry;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class Get
{
    public function execute(Request $request)
    {
        $projectId = $request->input('project_id');
        $anchor = $request->filled('date')
            ? Carbon::parse($request->input('date'))
            : Carbon::today();

        // Newest first throughout: latest day at the top, and within a day the latest
        // start time first, so the oldest entry is last. Entries predating the from/to
        // fields have no start time and sort last within their day.
        $query = TimeEntry::query()
            ->with(['project.rateModel'])
            ->orderBy('date', 'DESC')
            ->orderByRaw('time_from IS NULL')
            ->orderBy('time_from', 'DESC')
            ->orderBy('id', 'DESC');

        if ($projectId) {
            $query->where('project_id', $projectId);
        }

        $entries = $query->get();

        // Revenue for every billable project entry (cumulative, budget-capped).
        $engine = RevenueEngine::fromDatabase($projectId ? [$projectId] : null);
        $revenue = $engine->perEntryRevenue();

        // Group into days (newest first, only days with entries).
        $days = $entries
            ->groupBy(fn (TimeEntry $e) => $e->date->format('Y-m-d'))
            ->map(function ($group, $date) use ($revenue) {
                $carbon = Carbon::parse($date);
                return [
                    'date'          => $date,
                    'weekday_label' => $carbon->locale('de')->isoFormat('dddd, D. MMMM'),
                    'total_hours'   => round($group->sum(fn (TimeEntry $e) => (float) $e->hours), 2),
                    'total_revenue' => round($group->sum(fn (TimeEntry $e) => $revenue[$e->id]['revenue'] ?? 0), 2),
                    'entries'       => $group->map(fn (TimeEntry $e) => $this->transform($e, $revenue))->values(),
                ];
            })
            ->values();

        // Group the days into calendar weeks (newest week first).
        $weeks = $days
            ->groupBy(fn (array $day) => Carbon::parse($day['date'])->format('o-\\WW'))
            ->map(fn ($group, $key) => [
                'key'           => $key,
                'label'         => $this->weekLabel($group),
                'total_hours'   => round($group->sum('total_hours'), 2),
                'total_revenue' => round($group->sum('total_revenue'), 2),
                'days'          => $group->values(),
            ])
            ->values();

        $lastWeek  = $anchor->copy()->subWeek();
        $lastMonth = $anchor->copy()->subMonthNoOverflow();

        $weekStart      = $anchor->copy()->startOfWeek();
        $weekEnd        = $anchor->copy()->endOfWeek();
        $lastWeekStart  = $lastWeek->copy()->startOfWeek();
        $lastWeekEnd    = $lastWeek->copy()->endOfWeek();
        $monthStart     = $anchor->copy()->startOfMonth();
        $monthEnd       = $anchor->copy()->endOfMonth();
        $lastMonthStart = $lastMonth->copy()->startOfMonth();
        $lastMonthEnd   = $lastMonth->copy()->endOfMonth();

        return response()->json([
            'weeks' => $weeks,
            'stats' => [
                'day'              => $engine->periodRevenue($anchor->copy(), $anchor->copy()),
                'week'             => $engine->periodRevenue($weekStart, $weekEnd),
                'week_label'       => $this->rangeLabel($weekStart, $weekEnd),
                'last_week'        => $engine->periodRevenue($lastWeekStart, $lastWeekEnd),
                'month'            => $engine->periodRevenue($monthStart, $monthEnd),
                'month_label'      => $monthStart->locale('de')->isoFormat('MMMM YYYY'),
                'last_month'       => $engine->periodRevenue($lastMonthStart, $lastMonthEnd),
            ],
        ]);
    }

    /**
     * "7. September – 11. September" — the span of days actually booked in a week,
     * so a Mon-Fri week does not advertise an empty weekend.
     *
     * @param  \Illuminate\Support\Collection  $days  The week's days, newest first.
     */
    protected function weekLabel($days): string
    {
        $latest   = Carbon::parse($days->first()['date'])->locale('de');
        $earliest = Carbon::parse($days->last()['date'])->locale('de');

        if ($earliest->isSameDay($latest)) {
            return $earliest->isoFormat('D. MMMM');
        }

        return $earliest->isoFormat('D. MMMM') . ' – ' . $latest->isoFormat('D. MMMM');
    }

    /** "31.8. - 6.9." for a week range. */
    protected function rangeLabel(Carbon $from, Carbon $to): string
    {
        return $from->format('j.n.') . ' - ' . $to->format('j.n.');
    }

    protected function transform(TimeEntry $entry, array $revenue): array
    {
        $r = $revenue[$entry->id] ?? ['value' => 0, 'revenue' => 0, 'over_budget' => 0];

        return [
            'id'                  => $entry->id,
            'project_id'          => $entry->project_id,
            'activity'            => $entry->activity,
            'label'               => $entry->isActivity()
                ? $entry->activity
                : optional($entry->project)->name,
            'is_activity'         => $entry->isActivity(),
            'is_billable'         => (bool) $entry->is_billable,
            'date'                => $entry->date->format('Y-m-d'),
            'time_from'           => $entry->time_from,
            'time_to'             => $entry->time_to,
            'hours'               => (float) $entry->hours,
            'description'         => $entry->description,
            'rate'                => is_null($entry->rate) ? null : (float) $entry->rate,
            'resolved_rate'       => $entry->resolvedRate(),
            'value'               => $r['value'],
            'revenue'             => $r['revenue'],
            'over_budget'         => $r['over_budget'],
            'is_billed'           => $entry->isBilled(),
            'invoice_id'          => $entry->invoice_id,
        ];
    }
}
