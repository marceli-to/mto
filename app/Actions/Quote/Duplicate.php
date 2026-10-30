<?php

namespace App\Actions\Quote;

use App\Models\Quote;

class Duplicate
{
    public function execute(Quote $quote)
    {
        $clone = $quote->replicate();
        $clone->title = $quote->title . ' (copy)';
        $clone->status = Quote::DRAFT;
        $clone->save();

        $clone->generateNumber();

        foreach ($quote->pages as $page) {
            $clone->pages()->save($page->replicate(['quote_id']));
        }

        // Deep clone sections and positions
        foreach ($quote->sections as $section) {
            $clonedSection = $clone->sections()->save($section->replicate(['quote_id']));

            foreach ($section->positions as $position) {
                $clonedSection->positions()->save($position->replicate(['quote_section_id']));
            }
        }

        return response()->json($clone);
    }
}
