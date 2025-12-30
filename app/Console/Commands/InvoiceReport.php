<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Models\InvoiceState;
use Illuminate\Console\Command;
use Carbon\Carbon;

class InvoiceReport extends Command
{
    protected $signature = 'invoice:report';

    protected $description = 'Generate an invoice report for a specific period';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $from = $this->ask('Enter start date (DD.MM.YYYY)');
        $to = $this->ask('Enter end date (DD.MM.YYYY)');

        try {
            $fromDate = Carbon::createFromFormat('d.m.Y', $from);
            $toDate = Carbon::createFromFormat('d.m.Y', $to);
        } catch (\Exception $e) {
            $this->error('Invalid date format. Please use DD.MM.YYYY format');
            return 1;
        }

        if ($fromDate->gt($toDate)) {
            $this->error('Start date cannot be greater than end date');
            return 1;
        }

        $states = InvoiceState::all();
        $stateOptions = ['All invoices', 'Pending/Paid'];

        foreach ($states as $state) {
            $stateOptions[] = $state->description;
        }

        $selectedState = $this->choice('Filter by state', $stateOptions, 0);

        $stateFilter = null;
        if ($selectedState === 'Pending/Paid') {
            $stateFilter = 'pending_paid';
        } elseif ($selectedState !== 'All invoices') {
            $stateFilter = $states->firstWhere('description', $selectedState)->id ?? null;
        }

        $this->info("Invoice report from {$from} to {$to}");
        $this->line('');

        $query = Invoice::with(['client', 'state'])
            ->whereBetween('date', [$fromDate->format('Y-m-d'), $toDate->format('Y-m-d')])
            ->orderBy('date', 'asc');

        if ($stateFilter === 'pending_paid') {
            $pendingId = $states->first(fn($s) => strtolower($s->description) === 'pending')->id ?? null;
            $paidId = $states->first(fn($s) => strtolower($s->description) === 'paid')->id ?? null;
            $query->whereIn('state_id', array_filter([$pendingId, $paidId]));
        } elseif ($stateFilter) {
            $query->where('state_id', $stateFilter);
        }

        $invoices = $query->get();

        if ($invoices->isEmpty()) {
            $this->info('No invoices found for the specified period');
            return 0;
        }

        $grandTotal = 0;
        $tableData = [];

        foreach ($invoices as $invoice) {
            $amount = $invoice->grandtotal ?? $invoice->total ?? 0;
            $grandTotal += $amount;

            $tableData[] = [
                $invoice->number,
                Carbon::parse($invoice->date)->format('d.m.Y'),
                $invoice->client->name ?? 'N/A',
                $invoice->state->description ?? 'N/A',
                number_format($amount, 2),
            ];
        }

        $this->table(
            ['Number', 'Date', 'Client', 'State', 'Amount'],
            $tableData
        );

        $this->line('');
        $this->info("Total Invoices: " . $invoices->count());
        $this->info("Total Revenue: CHF " . number_format($grandTotal, 2));

        return 0;
    }
}