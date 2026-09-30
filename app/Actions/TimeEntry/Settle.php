<?php

namespace App\Actions\TimeEntry;

use App\Models\Invoice;
use App\Models\TimeEntry;
use Illuminate\Http\Request;

class Settle
{
    /**
     * Mark entries as covered by an invoice WITHOUT turning them into positions —
     * for fixed-price projects, whose invoice carries the flat price by hand. The
     * link is what takes them off the unbilled figures; Unbill reverses it.
     */
    public function execute(Request $request)
    {
        $request->validate([
            'invoice_id'       => 'required|exists:invoices,id',
            'time_entry_ids'   => 'required|array|min:1',
            'time_entry_ids.*' => 'integer|exists:time_entries,id',
        ]);

        $invoice = Invoice::findOrFail($request->input('invoice_id'));

        // Only billable, unbilled, project-attached entries can be settled.
        $settled = TimeEntry::query()
            ->whereIn('id', $request->input('time_entry_ids'))
            ->billable()
            ->unbilled()
            ->pluck('id');

        TimeEntry::whereIn('id', $settled)->update(['invoice_id' => $invoice->id]);

        return response()->json([
            'message' => 'Entries marked as billed.',
            'settled' => $settled,
            'skipped' => collect($request->input('time_entry_ids'))->diff($settled)->values(),
        ]);
    }
}
