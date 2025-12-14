<?php

namespace App\Actions\Project;

use App\Models\Project;
use App\Http\Requests\ProjectStoreRequest;

class Store
{
    public function execute(ProjectStoreRequest $request)
    {
        $project = new Project($request->all());
        $project->save();

        return response()->json(['projectId' => $project->id]);
    }
}
