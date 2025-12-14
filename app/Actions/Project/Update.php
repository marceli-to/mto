<?php

namespace App\Actions\Project;

use App\Models\Project;
use App\Http\Requests\ProjectStoreRequest;

class Update
{
    public function execute(Project $project, ProjectStoreRequest $request)
    {
        $project->update($request->all());

        return response()->json('successfully updated');
    }
}
