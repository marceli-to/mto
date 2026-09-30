<?php

namespace App\Actions\InvoiceState;

use App\Models\Invoice;
use App\Models\InvoiceState;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BulkUpdate
{
    /**
     * Set one state on a batch of invoices — mirrors the single-invoice Update.
     * A paid date only lands on invoices that actually change state, so
     * re-selecting an already paid invoice keeps its original date.
     */
    public function execute(Request $request)
    {
        $request->validate([
            'state_id'      => 'required|exists:states,id',
            'date_paid'     => 'nullable|date',
            'invoice_ids'   => 'required|array|min:1',
            'invoice_ids.*' => 'integer|exists:invoices,id',
        ]);

        $stateId = (int) $request->input('state_id');
        $datePaid = $request->input('date_paid');

        $invoices = Invoice::query()
            ->whereIn('id', $request->input('invoice_ids'))
            ->get();

        $updated = [];

        DB::transaction(function () use ($invoices, $stateId, $datePaid, &$updated) {
            foreach ($invoices as $invoice) {
                if ((int) $invoice->state_id === $stateId) {
                    continue;
                }

                // Set invoice amount to 'zero' if an invoice is cancelled
                if ($stateId === InvoiceState::CANCELLED) {
                    $invoice->total = 0;
                    $invoice->vat = 0;
                }

                $invoice->state_id = $stateId;
                if ($datePaid) {
                    $invoice->date_paid = $datePaid;
                }
                $invoice->save();

                $updated[] = $invoice->id;
            }
        });

        return response()->json([
            'message' => 'Invoices updated.',
            'updated' => $updated,
        ]);
    }
}
