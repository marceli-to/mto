<?php

namespace App\Http\Controllers\Api;

use App\Models\QuoteSection;
use App\Http\Controllers\Controller;

class QuoteSectionController extends Controller
{
    public function destroy(QuoteSection $quoteSection)
    {
        $quoteSection->positions()->delete();
        $quoteSection->delete();

        return response()->json('successfully deleted');
    }
}
