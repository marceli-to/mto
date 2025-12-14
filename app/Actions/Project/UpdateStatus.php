<?php

namespace App\Actions\Project;

use App\Models\Project;

class UpdateStatus
{
    public function execute(Project $project)
    {
        $project->publish = $project->publish == 0 ? 1 : 0;
        $project->save();

        return response()->json($project->publish);
    }
}
