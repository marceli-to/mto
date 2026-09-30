<?php

namespace App\Http\Controllers\Api;

use App\Models\Invoice;
use App\Http\Controllers\Controller;
use App\Http\Requests\InvoiceUpdateStateRequest;
use App\Actions\InvoiceState\Get as GetAction;
use App\Actions\InvoiceState\Update as UpdateAction;
use App\Actions\InvoiceState\BulkUpdate as BulkUpdateAction;
use Illuminate\Http\Request;

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

    public function bulkUpdate(Request $request)
    {
        return (new BulkUpdateAction)->execute($request);
    }
}
