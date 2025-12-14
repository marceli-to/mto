<?php

namespace App\Actions\Client;

use App\Models\Client;

class UpdateStatus
{
    public function execute(Client $client)
    {
        $client->publish = $client->publish == 0 ? 1 : 0;
        $client->save();

        return response()->json($client->publish);
    }
}
