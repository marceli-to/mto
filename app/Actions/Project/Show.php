<?php

namespace App\Actions\Project;

use App\Models\Project;

class Show
{
    public function execute(Project $project)
    {
        return response()->json($project);
    }
}
