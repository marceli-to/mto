<?php

namespace App\Actions\Liquidity;

use App\Models\LiquiditySnapshot;

class Delete
{
    public function execute(LiquiditySnapshot $snapshot)
    {
        $snapshot->delete();

        return response()->json('successfully deleted');
    }
}
