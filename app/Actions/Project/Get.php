<?php

namespace App\Actions\Project;

use App\Models\Project;
use App\Http\Resources\ProjectCollection;

class Get
{
    public function execute()
    {
        return new ProjectCollection(
            Project::with('client')->orderBy('name', 'ASC')->get()
        );
    }
}
