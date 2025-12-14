<?php

namespace App\Actions\Client;

use App\Models\Client;

class Delete
{
    public function execute(Client $client)
    {
        $client->delete();

        return response()->json('successfully deleted');
    }
}
