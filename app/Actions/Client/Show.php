<?php

namespace App\Actions\Client;

use App\Models\Client;

class Show
{
    public function execute(Client $client)
    {
        return response()->json($client);
    }
}
