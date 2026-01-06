<?php

namespace App\Actions\InvoiceState;

use App\Models\Invoice;
use App\Models\InvoiceState;
use App\Http\Requests\InvoiceUpdateStateRequest;

class Update
{
    public function execute(Invoice $invoice, InvoiceUpdateStateRequest $request)
    {
        // Set invoice amount to 'zero' if an invoice is cancelled
        if ($request->input('state_id') == InvoiceState::CANCELLED) {
            $invoice->total = 0;
            $invoice->vat = 0;
        }

        $invoice->update($request->all());

        return response()->json('successfully updated');
    }
}
