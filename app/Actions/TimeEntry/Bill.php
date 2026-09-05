<?php

namespace App\Actions\TimeEntry;

use App\Models\Invoice;
use App\Models\InvoicePosition;
use App\Models\TimeEntry;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class Bill
{
    public function execute(Request $request)
    {
        $request->validate([
            'invoice_id'       => 'required|exists:invoices,id',
            'time_entry_ids'   => 'required|array|min:1',
            'time_entry_ids.*' => 'integer|exists:time_entries,id',
        ]);

        $invoice = Invoice::findOrFail($request->input('invoice_id'));

        $entries = TimeEntry::query()
            ->with('project.rateModel')
            ->whereIn('id', $request->input('time_entry_ids'))
            ->get();

        $skipped = [];
        $created = [];

        DB::transaction(function () use ($entries, $invoice, &$skipped, &$created) {
            foreach ($entries as $entry) {
                // Only billable, unbilled, project-attached entries can be billed.
                if ($entry->isBilled() || !$entry->is_billable || is_null($entry->project_id)) {
                    $skipped[] = $entry->id;
                    continue;
                }

                $position = InvoicePosition::create([
                    'invoice_id'  => $invoice->id,
                    'periode'     => $entry->date->format('d.m.Y'),
                    'description' => $entry->description,
                    'rate'        => $entry->resolvedRate(),
                    'hours'       => $entry->hours,
                    'amount'      => $entry->value(),
                    'is_flat'     => false,
                    'is_fee'      => false,
                ]);

                $entry->invoice_id = $invoice->id;
                $entry->invoice_position_id = $position->id;
                $entry->save();

                $created[] = $position->id;
            }

            $invoice->recalculateTotal();
        });

        return response()->json([
            'message'         => 'Entries billed.',
            'created'         => $created,
            'skipped'         => $skipped,
            'invoice_total'   => $invoice->fresh()->total,
        ]);
    }
}
