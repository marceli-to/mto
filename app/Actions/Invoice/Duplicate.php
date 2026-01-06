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

        $clone->generateNumber();

        return response()->json($clone);
    }
}
