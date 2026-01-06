<?php

namespace App\Actions\Invoice;

use App\Models\Invoice;
use App\Http\Requests\InvoiceStoreRequest;

class Store
{
    public function execute(InvoiceStoreRequest $request)
    {
        $invoice = new Invoice($request->all());
        $invoice->save();

        $invoice->positions()->createMany($request->positions);
        $invoice->generateNumber();

        return response()->json(['invoiceId' => $invoice->id]);
    }
}
