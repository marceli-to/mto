<?php

namespace App\Actions\Invoice;

use App\Actions\TimeEntry\RevenueEngine;
use App\Models\Invoice;
use App\Models\InvoiceState;
use App\Models\Project;

class Get
{
    public function execute()
    {
        $invoices = Invoice::with('client', 'state')
            ->orderBy('state_id')
            ->orderBy('number', 'DESC')
            ->get();

        $states = InvoiceState::all();
        $totals = [];

        foreach ($states as $state) {
            $totals[$state->description] = $invoices->where('state_id', $state->id)->sum('grandtotal');
        }

        $totals['total'] = $invoices->reject->isCancelled()->sum('grandtotal');

        // Work on collection projects that is done but not invoiced yet is money owed
        // just like an open invoice, so it counts towards the open figure.
        $collectionIds = Project::where('is_collection', true)->pluck('id')->all();
        $totals['unbilled'] = $collectionIds
            ? RevenueEngine::fromDatabase($collectionIds)->unbilledRevenue()
            : 0.0;
        $totals['open'] = ($totals['open'] ?? 0) + $totals['unbilled'];

        return response()->json([
            'data' => $invoices,
            'totals' => $totals
        ]);
    }
}
