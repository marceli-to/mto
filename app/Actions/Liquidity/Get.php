<?php

namespace App\Actions\Liquidity;

use App\Models\LiquiditySnapshot;

class Get
{
    public function execute()
    {
        // Names only — the dropdown loads a full state on demand.
        return response()->json(
            LiquiditySnapshot::orderByDesc('updated_at')->get(['id', 'name', 'updated_at'])
        );
    }
}
