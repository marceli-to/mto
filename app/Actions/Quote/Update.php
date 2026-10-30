<?php

namespace App\Actions\Quote;

use App\Models\Quote;
use App\Http\Requests\QuoteStoreRequest;

class Update
{
    public function execute(Quote $quote, QuoteStoreRequest $request)
    {
        $quote->update($request->except('pages', 'sections'));

        (new SyncContent)->execute($quote, $request->input('pages', []), $request->input('sections', []));

        return response()->json('successfully updated');
    }
}
