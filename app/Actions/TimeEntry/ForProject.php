<?php

namespace App\Actions\TimeEntry;

use App\Models\Project;
use App\Models\TimeEntry;

class ForProject
{
    /**
     * All time entries booked on a project, newest first, with the budget-capped
     * revenue figures — the payload behind the project list's time flyout.
     */
    public function execute(Project $project)
    {
        $entries = TimeEntry::query()
            ->with('project.rateModel')
            ->where('project_id', $project->id)
            ->orderByDesc('date')
            ->orderByRaw('time_from IS NULL')
            ->orderByDesc('time_from')
            ->orderByDesc('id')
            ->get();

        $revenue = RevenueEngine::fromDatabase([$project->id])->perEntryRevenue();

        return response()->json([
            'project' => [
                'id'     => $project->id,
                'name'   => $project->name,
                'budget' => is_null($project->budget) ? null : (float) $project->budget,
            ],
            'totals' => [
                'hours'   => round($entries->sum(fn (TimeEntry $e) => (float) $e->hours), 2),
                'revenue' => round($entries->sum(fn (TimeEntry $e) => $revenue[$e->id]['revenue'] ?? 0), 2),
            ],
            'entries' => $entries->map(fn (TimeEntry $e) => [
                'id'          => $e->id,
                'date'        => $e->date->format('Y-m-d'),
                'periode'     => $e->date->format('d.m.Y'),
                'time_from'   => $e->time_from,
                'time_to'     => $e->time_to,
                'hours'       => (float) $e->hours,
                'description' => $e->description,
                'is_billable' => (bool) $e->is_billable,
                'is_billed'   => $e->isBilled(),
                'revenue'     => $revenue[$e->id]['revenue'] ?? 0,
            ])->values(),
        ]);
    }
}
