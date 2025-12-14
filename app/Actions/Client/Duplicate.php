<?php

namespace App\Actions\Client;

use App\Models\Client;

class Duplicate
{
    public function execute(Client $client)
    {
        $clone = $client->replicate();
        $clone->name = $client->name . ' (copy)';
        $clone->acronym = '';
        $clone->publish = 0;
        $clone->save();

        return response()->json($clone);
    }
}
