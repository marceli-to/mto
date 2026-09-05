<?php

namespace App\Actions\TimeEntry;

use App\Models\TimeEntry;
use App\Http\Requests\TimeEntryStoreRequest;

class Update
{
    public function execute(TimeEntry $timeEntry, TimeEntryStoreRequest $request)
    {
        if ($timeEntry->isBilled()) {
            return response()->json([
                'message' => 'This entry has been billed and cannot be edited. Unbill it first.',
            ], 422);
        }

        $timeEntry->update($request->validated());

        return response()->json($timeEntry);
    }
}
