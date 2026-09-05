<?php

namespace App\Actions\TimeEntry;

use App\Models\Project;
use App\Models\TimeEntry;

class UnbilledForProject
{
    /**
     * Unbilled, billable entries for a collection project — the candidates for
     * turning into invoice positions. Returns a lightweight preview payload.
     */
    public function execute(Project $project)
    {
        $entries = TimeEntry::query()
            ->with('project.rateModel')
            ->where('project_id', $project->id)
            ->billable()
            ->unbilled()
            ->orderBy('date')
            ->orderBy('id')
            ->get();

        return response()->json([
            'project' => [
                'id'            => $project->id,
                'name'          => $project->name,
                'client_id'     => $project->client_id,
                'is_collection' => (bool) $project->is_collection,
            ],
            'entries' => $entries->map(fn (TimeEntry $e) => [
                'id'          => $e->id,
                'date'        => $e->date->format('Y-m-d'),
                'periode'     => $e->date->format('d.m.Y'),
                'description' => $e->description,
                'hours'       => (float) $e->hours,
                'rate'        => $e->resolvedRate(),
                'amount'      => $e->value(),
            ])->values(),
        ]);
    }
}
