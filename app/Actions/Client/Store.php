<?php

namespace App\Actions\Client;

use App\Models\Client;
use App\Http\Requests\ClientStoreRequest;

class Store
{
    public function execute(ClientStoreRequest $request)
    {
        $client = new Client($request->all());
        $client->save();

        return response()->json(['clientId' => $client->id]);
    }
}
