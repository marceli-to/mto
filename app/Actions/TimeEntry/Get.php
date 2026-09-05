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

        // All entries for display (newest first), optionally filtered by project.
        $query = TimeEntry::query()
            ->with(['project.rateModel'])
            ->orderBy('date', 'DESC')
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
                    'weekday_label' => $carbon->locale('de')->isoFormat('dddd, D.M.'),
                    'total_hours'   => round($group->sum(fn (TimeEntry $e) => (float) $e->hours), 2),
                    'entries'       => $group->map(fn (TimeEntry $e) => $this->transform($e, $revenue))->values(),
                ];
            })
            ->values();

        return response()->json([
            'days'  => $days,
            'stats' => [
                'day'   => $engine->periodRevenue($anchor->copy(), $anchor->copy()),
                'week'  => $engine->periodRevenue($anchor->copy()->startOfWeek(), $anchor->copy()->endOfWeek()),
                'month' => $engine->periodRevenue($anchor->copy()->startOfMonth(), $anchor->copy()->endOfMonth()),
            ],
        ]);
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
