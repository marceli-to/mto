<?php

namespace App\Actions\Invoice;

use App\Models\Invoice;

class Delete
{
    public function execute(Invoice $invoice)
    {
        $invoice->delete();

        return response()->json('successfully deleted');
    }
}
