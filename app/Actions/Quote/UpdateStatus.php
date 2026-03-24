<?php

namespace App\Actions\Quote;

use App\Models\Quote;
use Illuminate\Http\Request;

class UpdateStatus
{
    public function execute(Quote $quote, Request $request)
    {
        $quote->status = $request->status;
        $quote->save();

        return response()->json('successfully updated');
    }
}
