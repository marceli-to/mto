<?php

namespace App\Actions\Rate;

use App\Models\Rate;
use App\Http\Resources\RateCollection;

class Get
{
    public function execute()
    {
        // Cheapest first, so the select reads as a ladder.
        return new RateCollection(Rate::orderBy('amount')->get());
    }
}
