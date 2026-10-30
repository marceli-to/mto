<?php

namespace App\Actions\Quote;

use App\Models\Quote;
use App\Http\Requests\QuoteStoreRequest;

class Store
{
    public function execute(QuoteStoreRequest $request)
    {
        $quote = new Quote($request->except('pages', 'sections'));
        $quote->status = Quote::DRAFT;
        $quote->save();

        (new SyncContent)->execute($quote, $request->input('pages', []), $request->input('sections', []));

        $quote->generateNumber();

        return response()->json(['quoteId' => $quote->id]);
    }
}
