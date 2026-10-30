<?php

namespace App\Actions\QuoteBlock;

use App\Models\QuoteBlock;

class Delete
{
    public function execute(QuoteBlock $block)
    {
        $block->delete();

        return response()->json('successfully deleted');
    }
}
