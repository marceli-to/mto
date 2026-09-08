<?php

namespace App\Actions\Project;

use App\Models\Project;

class Archive
{
    /**
     * Toggle a project between active and archived. Archived projects stay in the
     * database with their time entries and invoices intact — they are just filtered
     * out of the default list view.
     */
    public function execute(Project $project)
    {
        $project->is_archive = $project->is_archive ? 0 : 1;
        $project->save();

        return response()->json((new Present)->execute($project));
    }
}
