<?php

namespace App\Actions\Client;

use App\Models\Client;

class CheckUniqueAcronym
{
    public function execute(string $acronym)
    {
        $exists = Client::where('acronym', strtoupper($acronym))->exists();

        return response()->json(['exists' => $exists]);
    }
}
