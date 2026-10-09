<?php

namespace App\Actions\Liquidity;

use App\Actions\TimeEntry\RevenueEngine;
use App\Models\Invoice;
use App\Models\InvoiceState;
use App\Models\Project;

/**
 * Everything that can be picked into a liquidity state, with live amounts:
 * pending invoices (what they will pay in) and active projects not yet billed —
 * fixed-price projects at their budget, collections at their current unbilled value.
 */
class Sources
{
    public function execute()
    {
        $invoices = Invoice::with('client')
            ->where('state_id', InvoiceState::PENDING)
            ->orderBy('date_due')
            ->get()
            ->map(fn (Invoice $invoice) => [
                'id'       => $invoice->id,
                'number'   => $invoice->number,
                'title'    => $invoice->title,
                'client'   => $invoice->client?->name,
                'date_due' => $invoice->date_due,
                'amount'   => round((float) $invoice->grandtotal, 2),
            ])
            ->values();

        $unbilled = RevenueEngine::fromDatabase()->perProjectUnbilled();

        $projects = Project::with('client')
            ->where('is_archive', 0)
            ->orderBy('name')
            ->get()
            ->map(fn (Project $project) => [
                'id'            => $project->id,
                'name'          => $project->name,
                'client'        => $project->client?->name,
                'is_collection' => (bool) $project->is_collection,
                'amount'        => $project->is_collection
                    ? (float) ($unbilled[$project->id]['value'] ?? 0)
                    : round((float) $project->budget, 2),
            ])
            // Nothing to expect from a project worth nothing right now.
            ->filter(fn (array $project) => $project['amount'] > 0)
            ->values();

        return response()->json([
            'invoices' => $invoices,
            'projects' => $projects,
        ]);
    }
}
