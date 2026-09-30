<?php

namespace App\Actions\TimeEntry;

use App\Models\Project;
use App\Models\TimeEntry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class Move
{
    /**
     * Reassign a batch of entries to another project — the fix for time booked on
     * the wrong one. Billed entries are skipped: their invoice position was priced
     * against the old project, so they have to be unbilled first.
     */
    public function execute(Request $request)
    {
        $request->validate([
            'project_id'       => 'required|exists:projects,id',
            'time_entry_ids'   => 'required|array|min:1',
            'time_entry_ids.*' => 'integer|exists:time_entries,id',
        ]);

        $project = Project::findOrFail($request->input('project_id'));

        if ($project->is_archive) {
            return response()->json([
                'message' => 'Archived projects cannot take new time.',
            ], 422);
        }

        $entries = TimeEntry::query()
            ->whereIn('id', $request->input('time_entry_ids'))
            ->get();

        $skipped = [];
        $moved = [];

        DB::transaction(function () use ($entries, $project, &$skipped, &$moved) {
            foreach ($entries as $entry) {
                if ($entry->isBilled()) {
                    $skipped[] = $entry->id;
                    continue;
                }

                // An activity moved onto a project becomes regular project time,
                // billable by default — the same as picking a project in the form.
                if ($entry->isActivity()) {
                    $entry->activity = null;
                    $entry->is_billable = true;
                }

                // Rates always come from the project; drop any legacy override.
                $entry->project_id = $project->id;
                $entry->rate = null;
                $entry->save();

                $moved[] = $entry->id;
            }
        });

        return response()->json([
            'message' => 'Entries moved.',
            'moved'   => $moved,
            'skipped' => $skipped,
        ]);
    }
}
