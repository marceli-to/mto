<?php

namespace App\Actions\Client;

use App\Models\Client;
use App\Http\Requests\ClientStoreRequest;

class Update
{
    public function execute(Client $client, ClientStoreRequest $request)
    {
        $client->update($request->all());

        return response()->json('successfully updated');
    }
}
