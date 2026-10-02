<?php

namespace App\Actions\Project;

use App\Models\Project;
use App\Models\TimeEntry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class Archive
{
    /**
     * Toggle a project between active and archived. Archived projects stay in the
     * database with their time entries and invoices intact — they are just filtered
     * out of the default list view.
     *
     * When archiving, an optional invoice_id marks the project's unbilled entries as
     * billed by that invoice (e.g. work settled on an invoice written by hand), so
     * they don't linger as unbilled on an archived project.
     */
    public function execute(Project $project, Request $request)
    {
        $request->validate([
            'invoice_id' => 'nullable|exists:invoices,id',
        ]);

        DB::transaction(function () use ($project, $request) {
            $archiving = !$project->is_archive;

            if ($archiving && $request->filled('invoice_id')) {
                TimeEntry::query()
                    ->where('project_id', $project->id)
                    ->billable()
                    ->unbilled()
                    ->update(['invoice_id' => $request->input('invoice_id')]);
            }

            $project->is_archive = $archiving ? 1 : 0;
            $project->save();
        });

        return response()->json((new Present)->execute($project));
    }
}
