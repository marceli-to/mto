<?php

namespace App\Actions\Project;

use App\Models\Project;

class Delete
{
    public function execute(Project $project)
    {
        $project->delete();

        return response()->json('successfully deleted');
    }
}
