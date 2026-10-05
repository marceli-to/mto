<?php

namespace App\Actions\Payment;

use App\Models\Invoice;
use App\Models\InvoiceState;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class Apply
{
    /**
     * Mark the confirmed invoices paid, each on the date its payment was
     * booked. Invoices that are already paid keep their original date.
     */
    public function execute(Request $request)
    {
        $request->validate([
            'payments'              => 'required|array|min:1',
            'payments.*.invoice_id' => 'required|integer|distinct|exists:invoices,id',
            'payments.*.date_paid'  => 'required|date',
        ]);

        $payments = collect($request->input('payments'))->keyBy('invoice_id');

        $invoices = Invoice::query()
            ->whereIn('id', $payments->keys())
            ->get();

        $updated = [];

        DB::transaction(function () use ($invoices, $payments, &$updated) {
            foreach ($invoices as $invoice) {
                if ($invoice->isPaid() || $invoice->isCancelled()) {
                    continue;
                }

                $invoice->state_id = InvoiceState::PAID;
                $invoice->date_paid = $payments[$invoice->id]['date_paid'];
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
