<?php

namespace App\Actions\QuoteBlock;

use App\Models\QuoteBlock;
use App\Http\Requests\QuoteBlockStoreRequest;

class Store
{
    public function execute(QuoteBlockStoreRequest $request)
    {
        $block = QuoteBlock::create([
            ...$request->validated(),
            'sort_order' => (QuoteBlock::max('sort_order') ?? -1) + 1,
        ]);

        return response()->json($block);
    }
}
