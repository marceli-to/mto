<?php

namespace App\Actions\Liquidity;

use App\Models\LiquiditySnapshot;
use App\Http\Requests\LiquiditySnapshotStoreRequest;

class Update
{
    public function execute(LiquiditySnapshot $snapshot, LiquiditySnapshotStoreRequest $request)
    {
        $snapshot->update([
            'name' => $request->input('name'),
            'data' => $request->snapshotData(),
        ]);

        return response()->json($snapshot);
    }
}
