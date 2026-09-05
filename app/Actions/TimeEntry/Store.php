<?php

namespace App\Actions\TimeEntry;

use App\Models\TimeEntry;
use App\Http\Requests\TimeEntryStoreRequest;

class Store
{
    public function execute(TimeEntryStoreRequest $request)
    {
        $entry = new TimeEntry($request->validated());
        $entry->save();

        return response()->json($entry);
    }
}
