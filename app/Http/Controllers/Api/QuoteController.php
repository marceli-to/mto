<?php

namespace App\Http\Controllers\Api;

use App\Models\Quote;
use App\Http\Controllers\Controller;
use App\Http\Requests\QuoteStoreRequest;
use App\Actions\Quote\Get as GetAction;
use App\Actions\Quote\Show as ShowAction;
use App\Actions\Quote\Store as StoreAction;
use App\Actions\Quote\Update as UpdateAction;
use App\Actions\Quote\UpdateStatus as UpdateStatusAction;
use App\Actions\Quote\Delete as DeleteAction;
use App\Actions\Quote\Duplicate as DuplicateAction;
use Illuminate\Http\Request;

class QuoteController extends Controller
{
    public function get()
    {
        return (new GetAction)->execute();
    }

    public function store(QuoteStoreRequest $request)
    {
        return (new StoreAction)->execute($request);
    }

    public function edit(Quote $quote)
    {
        return (new ShowAction)->execute($quote);
    }

    public function update(Quote $quote, QuoteStoreRequest $request)
    {
        return (new UpdateAction)->execute($quote, $request);
    }

    public function updateStatus(Quote $quote, Request $request)
    {
        return (new UpdateStatusAction)->execute($quote, $request);
    }

    public function duplicate(Quote $quote)
    {
        return (new DuplicateAction)->execute($quote);
    }

    public function destroy(Quote $quote)
    {
        return (new DeleteAction)->execute($quote);
    }
}
