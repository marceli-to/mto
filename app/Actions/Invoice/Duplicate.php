<?php

namespace App\Actions\Invoice;

use App\Models\Invoice;

class Duplicate
{
    public function execute(Invoice $invoice)
    {
        $clone = $invoice->replicate();
        $clone->title = $invoice->title . ' (copy)';
        $clone->save();

        $this->createInvoiceNumber($clone);

        return response()->json($clone);
    }

    protected function createInvoiceNumber(Invoice $invoice): void
    {
        $invoice->number = date('y', time()) . '.' . str_pad($invoice->id, 4, "0", STR_PAD_LEFT);
        $invoice->save();
    }
}
