<?php

namespace App\Actions\Quote;

use App\Models\Quote;

class Delete
{
    public function execute(Quote $quote)
    {
        $quote->delete();

        return response()->json('successfully deleted');
    }
}
