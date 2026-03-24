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

        // Deep clone sections and positions
        foreach ($quote->sections as $section) {
            $clonedSection = $clone->sections()->create([
                'title' => $section->title,
                'total_label' => $section->total_label,
                'sort_order' => $section->sort_order,
            ]);

            foreach ($section->positions as $position) {
                $clonedSection->positions()->create([
                    'description' => $position->description,
                    'amount' => $position->amount,
                    'sort_order' => $position->sort_order,
                ]);
            }
        }

        return response()->json($clone);
    }
}
