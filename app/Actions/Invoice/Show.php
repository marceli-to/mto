<?php

namespace App\Actions\Invoice;

use App\Models\Invoice;

class Show
{
    public function execute(Invoice $invoice)
    {
        return response()->json($invoice->load('positions', 'state'));
    }
}
