<?php

namespace App\Actions\Quote;

use App\Models\Quote;
use App\Http\Requests\QuoteStoreRequest;

class Store
{
    public function execute(QuoteStoreRequest $request)
    {
        $quote = new Quote($request->except('sections'));
        $quote->status = Quote::DRAFT;
        $quote->save();

        if ($request->has('sections')) {
            foreach ($request->sections as $sectionData) {
                $section = $quote->sections()->create([
                    'title' => $sectionData['title'],
                    'total_label' => $sectionData['total_label'] ?? 'Total',
                    'sort_order' => $sectionData['sort_order'] ?? 0,
                ]);

                if (!empty($sectionData['positions'])) {
                    foreach ($sectionData['positions'] as $positionData) {
                        $section->positions()->create([
                            'description' => $positionData['description'],
                            'amount' => $positionData['amount'] ?? 0,
                            'sort_order' => $positionData['sort_order'] ?? 0,
                        ]);
                    }
                }
            }
        }

        $quote->generateNumber();

        return response()->json(['quoteId' => $quote->id]);
    }
}
