<?php

namespace App\Actions\Liquidity;

use App\Models\LiquiditySnapshot;

class Show
{
    public function execute(LiquiditySnapshot $snapshot)
    {
        return response()->json($snapshot);
    }
}
