<?php

namespace App\Http\Controllers\Api;

use App\Models\Invoice;
use App\Http\Controllers\Controller;
use App\Http\Requests\InvoiceStoreRequest;
use App\Http\Requests\InvoiceSendRequest;
use App\Actions\Invoice\Get as GetAction;
use App\Actions\Invoice\Show as ShowAction;
use App\Actions\Invoice\Store as StoreAction;
use App\Actions\Invoice\Update as UpdateAction;
use App\Actions\Invoice\Delete as DeleteAction;
use App\Actions\Invoice\Duplicate as DuplicateAction;
use App\Actions\Invoice\Send as SendAction;
use App\Actions\Invoice\MailDefaults as MailDefaultsAction;

class InvoiceController extends Controller
{
    public function get()
    {
        return (new GetAction)->execute();
    }

    public function store(InvoiceStoreRequest $request)
    {
        return (new StoreAction)->execute($request);
    }

    public function edit(Invoice $invoice)
    {
        return (new ShowAction)->execute($invoice);
    }

    public function update(Invoice $invoice, InvoiceStoreRequest $request)
    {
        return (new UpdateAction)->execute($invoice, $request);
    }

    public function mail(Invoice $invoice)
    {
        return (new MailDefaultsAction)->execute($invoice);
    }

    public function send(Invoice $invoice, InvoiceSendRequest $request)
    {
        return (new SendAction)->execute($invoice, $request);
    }

    public function duplicate(Invoice $invoice)
    {
        return (new DuplicateAction)->execute($invoice);
    }

    public function destroy(Invoice $invoice)
    {
        return (new DeleteAction)->execute($invoice);
    }
}
