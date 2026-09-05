<?php

namespace App\Actions\TimeEntry;

use App\Models\TimeEntry;

class Delete
{
    public function execute(TimeEntry $timeEntry)
    {
        if ($timeEntry->isBilled()) {
            return response()->json([
                'message' => 'This entry has been billed and cannot be deleted. Unbill it first.',
            ], 422);
        }

        $timeEntry->delete();

        return response()->json('successfully deleted');
    }
}
