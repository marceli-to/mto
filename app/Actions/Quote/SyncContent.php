<?php

namespace App\Actions\Quote;

use App\Models\Quote;
use App\Models\QuotePage;

/**
 * Writes the submitted pages, sections and positions of a quote, updating
 * the ones that have an id, creating the rest and removing what's missing.
 */
class SyncContent
{
    public function execute(Quote $quote, array $pages, array $sections): void
    {
        $pageIds = [];
        foreach (array_values($pages) as $i => $pageData) {
            $attributes = [
                'type' => in_array($pageData['type'] ?? null, QuotePage::TYPES) ? $pageData['type'] : QuotePage::TEXT,
                'content' => $pageData['content'] ?? null,
                'sort_order' => $i,
            ];

            $page = !empty($pageData['id']) ? $quote->pages()->find($pageData['id']) : null;
            if ($page) {
                $page->update($attributes);
            } else {
                $page = $quote->pages()->create($attributes);
            }

            $pageIds[] = $page->id;
        }
        $quote->pages()->whereNotIn('id', $pageIds)->delete();

        $sectionIds = [];
        foreach (array_values($sections) as $si => $sectionData) {
            $attributes = [
                'title' => $sectionData['title'] ?? '',
                'total_label' => $sectionData['total_label'] ?? 'Total',
                'show_total' => (bool) ($sectionData['show_total'] ?? true),
                'show_vat_note' => (bool) ($sectionData['show_vat_note'] ?? true),
                'currency' => $sectionData['currency'] ?? 'CHF',
                'sort_order' => $si,
            ];

            $section = !empty($sectionData['id']) ? $quote->sections()->find($sectionData['id']) : null;
            if ($section) {
                $section->update($attributes);
            } else {
                $section = $quote->sections()->create($attributes);
            }

            $sectionIds[] = $section->id;

            $positionIds = [];
            foreach (array_values($sectionData['positions'] ?? []) as $pi => $positionData) {
                $attributes = [
                    'description' => $positionData['description'],
                    'amount' => $positionData['amount'] ?? 0,
                    'amount_text' => ($positionData['amount_text'] ?? null) ?: null,
                    'sort_order' => $pi,
                ];

                $position = !empty($positionData['id']) ? $section->positions()->find($positionData['id']) : null;
                if ($position) {
                    $position->update($attributes);
                } else {
                    $position = $section->positions()->create($attributes);
                }

                $positionIds[] = $position->id;
            }
            $section->positions()->whereNotIn('id', $positionIds)->delete();
        }
        $quote->sections()->whereNotIn('id', $sectionIds)->delete();

        // Page and position edits don't change the quote row itself, but the
        // cached PDF is keyed on its updated_at.
        $quote->touch();
    }
}
