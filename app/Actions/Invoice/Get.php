<?php

namespace App\Actions\Invoice;

use App\Models\Invoice;
use App\Models\InvoiceState;

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

        return response()->json([
            'data' => $invoices,
            'totals' => $totals
        ]);
    }
}
