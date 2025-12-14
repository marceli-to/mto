<?php

namespace App\Actions\Rate;

use App\Models\Rate;
use App\Http\Resources\RateCollection;

class Get
{
    public function execute()
    {
        return new RateCollection(Rate::all());
    }
}
