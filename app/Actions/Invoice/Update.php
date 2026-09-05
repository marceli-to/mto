<?php

namespace App\Actions\Invoice;

use App\Models\Invoice;
use App\Models\InvoicePosition;
use App\Http\Requests\InvoiceStoreRequest;

class Update
{
    public function execute(Invoice $invoice, InvoiceStoreRequest $request)
    {
        $invoice->update($request->all());

        foreach ($request->positions as $position) {
            $position['invoice_id'] = $invoice->id;
            if (isset($position['id'])) {
                InvoicePosition::updateOrCreate(['id' => $position['id']], $position);
            } else {
                InvoicePosition::create($position);
            }
        }

        // Recompute total/vat/grandtotal from the persisted positions (source of truth).
        $invoice->recalculateTotal();

        return response()->json('successfully updated');
    }
}
