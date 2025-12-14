<?php

namespace App\Actions\InvoicePosition;

use App\Models\InvoicePosition;

class Delete
{
    public function execute(InvoicePosition $invoicePosition)
    {
        $invoicePosition->delete();

        return response()->json('successfully deleted');
    }
}
