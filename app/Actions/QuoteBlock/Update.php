<?php

namespace App\Actions\QuoteBlock;

use App\Models\QuoteBlock;
use App\Http\Requests\QuoteBlockStoreRequest;

class Update
{
    public function execute(QuoteBlock $block, QuoteBlockStoreRequest $request)
    {
        $block->update($request->validated());

        return response()->json($block);
    }
}
