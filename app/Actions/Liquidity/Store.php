<?php

namespace App\Actions\Liquidity;

use App\Models\LiquiditySnapshot;
use App\Http\Requests\LiquiditySnapshotStoreRequest;

class Store
{
    public function execute(LiquiditySnapshotStoreRequest $request)
    {
        $snapshot = LiquiditySnapshot::create([
            'name' => $request->input('name'),
            'data' => $request->snapshotData(),
        ]);

        return response()->json($snapshot);
    }
}
