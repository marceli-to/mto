<?php

namespace App\Http\Controllers\Api;

use App\Models\Invoice;
use App\Http\Controllers\Controller;
use App\Http\Requests\InvoiceUpdateStateRequest;
use App\Actions\InvoiceState\Get as GetAction;
use App\Actions\InvoiceState\Update as UpdateAction;

class InvoiceStateController extends Controller
{
    public function index()
    {
        return (new GetAction)->execute();
    }

    public function update(Invoice $invoice, InvoiceUpdateStateRequest $request)
    {
        return (new UpdateAction)->execute($invoice, $request);
    }
}
