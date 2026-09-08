<?php

namespace App\Actions\Project;

use App\Actions\TimeEntry\RevenueEngine;
use App\Models\Project;
use App\Http\Resources\ProjectCollection;

class Get
{
    public function execute()
    {
        $projects = Project::with('client')->orderBy('name', 'ASC')->get();

        // Time booked per project, so the list can show what each project has consumed:
        // revenue for collection projects, budget burn for fixed ones.
        $totals = RevenueEngine::fromDatabase()->perProjectTotals();

        foreach ($projects as $project) {
            $t = $totals[$project->id] ?? ['hours' => 0.0, 'value' => 0.0, 'revenue' => 0.0];
            $budget = (float) $project->budget;

            $project->setAttribute('hours_spent', $t['hours']);
            $project->setAttribute('revenue', $t['revenue']);
            $project->setAttribute('budget_used', $budget > 0
                ? round($t['value'] / $budget * 100)
                : null);
        }

        return new ProjectCollection($projects);
    }
}
