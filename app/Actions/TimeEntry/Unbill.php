<?php

namespace App\Actions\TimeEntry;

use App\Models\Invoice;
use App\Models\InvoicePosition;
use App\Models\TimeEntry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class Unbill
{
    public function execute(Request $request)
    {
        $request->validate([
            'time_entry_ids'   => 'required|array|min:1',
            'time_entry_ids.*' => 'integer|exists:time_entries,id',
        ]);

        $entries = TimeEntry::query()
            ->whereIn('id', $request->input('time_entry_ids'))
            ->whereNotNull('invoice_id')
            ->get();

        $affectedInvoiceIds = [];

        DB::transaction(function () use ($entries, &$affectedInvoiceIds) {
            foreach ($entries as $entry) {
                $affectedInvoiceIds[$entry->invoice_id] = true;

                if ($entry->invoice_position_id) {
                    InvoicePosition::where('id', $entry->invoice_position_id)->delete();
                }

                $entry->invoice_id = null;
                $entry->invoice_position_id = null;
                $entry->save();
            }

            // Recompute totals for every affected invoice.
            foreach (array_keys($affectedInvoiceIds) as $invoiceId) {
                optional(Invoice::find($invoiceId))->recalculateTotal();
            }
        });

        return response()->json([
            'message'  => 'Entries unbilled.',
            'unbilled' => $entries->pluck('id'),
        ]);
    }
}
