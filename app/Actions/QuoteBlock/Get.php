<?php

namespace App\Actions\QuoteBlock;

use App\Models\QuoteBlock;

class Get
{
    public function execute()
    {
        return response()->json([
            'data' => QuoteBlock::orderBy('sort_order')->orderBy('title')->get(),
        ]);
    }
}
