<?php

namespace App\Actions\TimeEntry;

use App\Models\TimeEntry;

class LastEnd
{
    /**
     * The latest end time booked on a given date — used to hint the next
     * entry's start time in the form.
     */
    public function execute(string $date)
    {
        $entry = TimeEntry::query()
            ->whereDate('date', $date)
            ->whereNotNull('time_to')
            ->orderByDesc('time_to')
            ->first();

        return response()->json([
            'time_to' => $entry?->time_to,
        ]);
    }
}
