<?php

namespace App\Observers;

use App\Models\Quote;

class QuoteObserver
{
    public function deleting(Quote $quote)
    {
        $quote->sections->each(function ($section) {
            $section->positions()->delete();
        });
        $quote->sections()->delete();
    }
}
