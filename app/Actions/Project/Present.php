<?php

namespace App\Actions\Project;

use App\Actions\TimeEntry\RevenueEngine;
use App\Models\Project;

/**
 * Shape a single project the same way the list does, so a project saved in the
 * flyout can be dropped straight into the list without a refetch.
 */
class Present
{
    public function execute(Project $project): Project
    {
        $project->load('client');

        $engine = RevenueEngine::fromDatabase();
        $t = $engine->perProjectTotals()[$project->id] ?? ['hours' => 0.0, 'value' => 0.0, 'revenue' => 0.0];
        $u = $engine->perProjectUnbilled()[$project->id] ?? ['count' => 0, 'value' => 0.0];
        $budget = (float) $project->budget;

        $project->setAttribute('hours_spent', $t['hours']);
        $project->setAttribute('revenue', $t['revenue']);
        $project->setAttribute('unbilled_count', $u['count']);
        $project->setAttribute('unbilled_value', $u['value']);
        $project->setAttribute('budget_used', $budget > 0
            ? round($t['value'] / $budget * 100)
            : null);

        return $project;
    }
}
