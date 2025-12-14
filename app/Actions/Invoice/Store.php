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

        $this->createInvoiceNumber($invoice);

        return response()->json(['invoiceId' => $invoice->id]);
    }

    protected function createInvoiceNumber(Invoice $invoice): void
    {
        $invoice->number = date('y', time()) . '.' . str_pad($invoice->id, 4, "0", STR_PAD_LEFT);
        $invoice->save();
    }
}
