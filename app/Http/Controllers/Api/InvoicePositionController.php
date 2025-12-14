<?php

namespace App\Http\Controllers\Api;

use App\Models\InvoicePosition;
use App\Http\Controllers\Controller;
use App\Actions\InvoicePosition\Delete as DeleteAction;

class InvoicePositionController extends Controller
{
    public function destroy(InvoicePosition $invoicePosition)
    {
        return (new DeleteAction)->execute($invoicePosition);
    }
}
