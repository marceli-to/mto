<?php

namespace App\Actions\Project;

use App\Models\Project;

class Duplicate
{
    public function execute(Project $project)
    {
        $clone = $project->replicate();
        $clone->name = $project->name . ' (copy)';
        $clone->is_archive = 0;
        $clone->save();

        return response()->json($clone);
    }
}
