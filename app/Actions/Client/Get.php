<?php

namespace App\Actions\Client;

use App\Models\Client;
use App\Http\Resources\ClientCollection;

class Get
{
    public function execute()
    {
        return new ClientCollection(Client::orderBy('name', 'ASC')->get());
    }
}
