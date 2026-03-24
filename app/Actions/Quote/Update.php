<?php

namespace App\Actions\Quote;

use App\Models\Quote;
use App\Models\QuoteSection;
use App\Models\QuotePosition;
use App\Http\Requests\QuoteStoreRequest;

class Update
{
    public function execute(Quote $quote, QuoteStoreRequest $request)
    {
        $quote->update($request->except('sections'));

        $submittedSectionIds = [];
        $submittedPositionIds = [];

        if ($request->has('sections')) {
            foreach ($request->sections as $sectionData) {
                if (!empty($sectionData['id'])) {
                    $section = QuoteSection::find($sectionData['id']);
                    $section->update([
                        'title' => $sectionData['title'],
                        'total_label' => $sectionData['total_label'] ?? 'Total',
                        'sort_order' => $sectionData['sort_order'] ?? 0,
                    ]);
                } else {
                    $section = $quote->sections()->create([
                        'title' => $sectionData['title'],
                        'total_label' => $sectionData['total_label'] ?? 'Total',
                        'sort_order' => $sectionData['sort_order'] ?? 0,
                    ]);
                }

                $submittedSectionIds[] = $section->id;

                if (!empty($sectionData['positions'])) {
                    foreach ($sectionData['positions'] as $positionData) {
                        if (!empty($positionData['id'])) {
                            $position = QuotePosition::find($positionData['id']);
                            $position->update([
                                'description' => $positionData['description'],
                                'amount' => $positionData['amount'] ?? 0,
                                'sort_order' => $positionData['sort_order'] ?? 0,
                            ]);
                        } else {
                            $position = $section->positions()->create([
                                'description' => $positionData['description'],
                                'amount' => $positionData['amount'] ?? 0,
                                'sort_order' => $positionData['sort_order'] ?? 0,
                            ]);
                        }

                        $submittedPositionIds[] = $position->id;
                    }
                }

                // Remove positions not in the submitted data
                $section->positions()->whereNotIn('id', $submittedPositionIds)->delete();
                $submittedPositionIds = [];
            }
        }

        // Remove sections not in the submitted data
        $quote->sections()->whereNotIn('id', $submittedSectionIds)->delete();

        return response()->json('successfully updated');
    }
}
