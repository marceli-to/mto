<?php

namespace App\Actions\TimeEntry;

use App\Models\TimeEntry;

class Show
{
    public function execute(TimeEntry $timeEntry)
    {
        return response()->json($timeEntry->load('project.rateModel'));
    }
}
