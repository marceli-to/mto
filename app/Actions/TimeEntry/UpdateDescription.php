<?php

namespace App\Actions\TimeEntry;

use App\Models\TimeEntry;
use Illuminate\Http\Request;

class UpdateDescription
{
    /**
     * Inline edit from the project's time flyout — the description only, so the
     * caller doesn't have to resend times and rates.
     */
    public function execute(TimeEntry $timeEntry, Request $request)
    {
        if ($timeEntry->isBilled()) {
            return response()->json([
                'message' => 'This entry has been billed and cannot be edited. Unbill it first.',
            ], 422);
        }

        $validated = $request->validate([
            'description' => 'nullable|string',
        ]);

        $timeEntry->update(['description' => $validated['description'] ?? null]);

        return response()->json($timeEntry);
    }
}
