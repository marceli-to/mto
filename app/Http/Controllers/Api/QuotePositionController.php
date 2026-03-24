<?php

namespace App\Http\Controllers\Api;

use App\Models\QuotePosition;
use App\Http\Controllers\Controller;

class QuotePositionController extends Controller
{
    public function destroy(QuotePosition $quotePosition)
    {
        $quotePosition->delete();

        return response()->json('successfully deleted');
    }
}
