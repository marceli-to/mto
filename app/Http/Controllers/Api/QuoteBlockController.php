<?php

namespace App\Http\Controllers\Api;

use App\Models\QuoteBlock;
use App\Http\Controllers\Controller;
use App\Http\Requests\QuoteBlockStoreRequest;
use App\Actions\QuoteBlock\Get as GetAction;
use App\Actions\QuoteBlock\Store as StoreAction;
use App\Actions\QuoteBlock\Update as UpdateAction;
use App\Actions\QuoteBlock\Delete as DeleteAction;

class QuoteBlockController extends Controller
{
    public function get()
    {
        return (new GetAction)->execute();
    }

    public function store(QuoteBlockStoreRequest $request)
    {
        return (new StoreAction)->execute($request);
    }

    public function update(QuoteBlock $quoteBlock, QuoteBlockStoreRequest $request)
    {
        return (new UpdateAction)->execute($quoteBlock, $request);
    }

    public function destroy(QuoteBlock $quoteBlock)
    {
        return (new DeleteAction)->execute($quoteBlock);
    }
}
