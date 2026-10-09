<?php

namespace App\Http\Controllers\Api;

use App\Models\LiquiditySnapshot;
use App\Http\Controllers\Controller;
use App\Http\Requests\LiquiditySnapshotStoreRequest;
use App\Actions\Liquidity\Sources as SourcesAction;
use App\Actions\Liquidity\Get as GetAction;
use App\Actions\Liquidity\Show as ShowAction;
use App\Actions\Liquidity\Store as StoreAction;
use App\Actions\Liquidity\Update as UpdateAction;
use App\Actions\Liquidity\Delete as DeleteAction;

class LiquidityController extends Controller
{
    public function sources()
    {
        return (new SourcesAction)->execute();
    }

    public function get()
    {
        return (new GetAction)->execute();
    }

    public function store(LiquiditySnapshotStoreRequest $request)
    {
        return (new StoreAction)->execute($request);
    }

    public function edit(LiquiditySnapshot $snapshot)
    {
        return (new ShowAction)->execute($snapshot);
    }

    public function update(LiquiditySnapshot $snapshot, LiquiditySnapshotStoreRequest $request)
    {
        return (new UpdateAction)->execute($snapshot, $request);
    }

    public function destroy(LiquiditySnapshot $snapshot)
    {
        return (new DeleteAction)->execute($snapshot);
    }
}
