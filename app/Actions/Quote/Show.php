<?php

namespace App\Actions\Quote;

use App\Models\Quote;

class Show
{
    public function execute(Quote $quote)
    {
        return response()->json($quote->load('pages', 'sections.positions', 'client'));
    }
}
