<?php

namespace App\Actions\Quote;

use App\Models\Quote;

class Get
{
    public function execute()
    {
        $quotes = Quote::with('client', 'sections.positions')
            ->orderBy('number', 'DESC')
            ->get();

        return response()->json([
            'data' => $quotes,
        ]);
    }
}
