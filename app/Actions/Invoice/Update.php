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

        $positions = [];
        $total = 0;

        foreach ($request->positions as $position) {
            $position['invoice_id'] = $invoice->id;
            $total += $position['amount'];
            if (isset($position['id'])) {
                InvoicePosition::updateOrCreate(['id' => $position['id']], $position);
            } else {
                InvoicePosition::create($position);
            }
        }

        $invoice->total = $total;
        $invoice->save();

        return response()->json('successfully updated');
    }
}
