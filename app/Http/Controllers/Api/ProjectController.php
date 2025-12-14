<?php

namespace App\Http\Controllers\Api;

use App\Models\Project;
use App\Http\Controllers\Controller;
use App\Http\Requests\ProjectStoreRequest;
use App\Actions\Project\Get as GetAction;
use App\Actions\Project\Show as ShowAction;
use App\Actions\Project\Store as StoreAction;
use App\Actions\Project\Update as UpdateAction;
use App\Actions\Project\Delete as DeleteAction;
use App\Actions\Project\Duplicate as DuplicateAction;
use App\Actions\Project\UpdateStatus as UpdateStatusAction;

class ProjectController extends Controller
{
    public function get()
    {
        return (new GetAction)->execute();
    }

    public function store(ProjectStoreRequest $request)
    {
        return (new StoreAction)->execute($request);
    }

    public function edit(Project $project)
    {
        return (new ShowAction)->execute($project);
    }

    public function update(Project $project, ProjectStoreRequest $request)
    {
        return (new UpdateAction)->execute($project, $request);
    }

    public function duplicate(Project $project)
    {
        return (new DuplicateAction)->execute($project);
    }

    public function status(Project $project)
    {
        return (new UpdateStatusAction)->execute($project);
    }

    public function destroy(Project $project)
    {
        return (new DeleteAction)->execute($project);
    }
}
