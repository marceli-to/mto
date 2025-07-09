<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Models\InvoiceState;
use Illuminate\Console\Command;
use Carbon\Carbon;

class InvoiceReport extends Command
{
    protected $signature = 'invoice:report';

    protected $description = 'Generate an invoice report for a specific period with totals by state';

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
        $stateOptions = ['Skip - Show open/pending/paid report'];
        $stateMap = [0 => 'Skip - Show open/pending/paid report'];
        
        foreach ($states as $state) {
            $stateOptions[] = $state->description;
            $stateMap[$state->id] = $state->description;
        }

        $selectedState = $this->choice('Select a state (optional)', $stateOptions, 0);
        $stateId = array_search($selectedState, $stateMap);
        
        if ($stateId === 0) {
            $stateId = null;
        }

        $this->info("Generating invoice report from {$from} to {$to}");
        $this->line('');

        $query = Invoice::with('state')
            ->whereBetween('date', [$fromDate->format('Y.m.d'), $toDate->format('Y.m.d')]);

        if ($stateId) {
            $query->where('state_id', $stateId);
        }

        $invoices = $query->get();

        if ($invoices->isEmpty()) {
            $this->info('No invoices found for the specified period');
            return 0;
        }

        $stateMapForReport = $states->keyBy('id');

        if ($stateId === null) {
            // Show open/pending/paid report
            $reportData = [
                'open' => ['name' => 'Open', 'count' => 0, 'total' => 0],
                'pending' => ['name' => 'Pending', 'count' => 0, 'total' => 0],
                'paid' => ['name' => 'Paid', 'count' => 0, 'total' => 0]
            ];
            
            $grandTotal = 0;
            
            foreach ($invoices as $invoice) {
                $invoiceStateId = $invoice->state_id;
                $stateName = strtolower($stateMapForReport[$invoiceStateId]->description ?? 'unknown');
                $amount = $invoice->grandtotal ?? $invoice->total ?? 0;
                
                if (isset($reportData[$stateName])) {
                    $reportData[$stateName]['count']++;
                    $reportData[$stateName]['total'] += $amount;
                }
                
                $grandTotal += $amount;
            }
            
            $this->table(
                ['State', 'Count', 'Total Amount'],
                collect($reportData)->map(function ($data) {
                    return [
                        $data['name'],
                        $data['count'],
                        number_format($data['total'], 2)
                    ];
                })->toArray()
            );
        } else {
            // Show specific state report
            $reportData = [];
            $grandTotal = 0;

            foreach ($invoices as $invoice) {
                $invoiceStateId = $invoice->state_id;
                $stateName = $stateMapForReport[$invoiceStateId]->description ?? 'Unknown';

                if (!isset($reportData[$invoiceStateId])) {
                    $reportData[$invoiceStateId] = [
                        'name' => $stateName,
                        'count' => 0,
                        'total' => 0
                    ];
                }

                $reportData[$invoiceStateId]['count']++;
                $reportData[$invoiceStateId]['total'] += $invoice->grandtotal ?? $invoice->total ?? 0;
                $grandTotal += $invoice->grandtotal ?? $invoice->total ?? 0;
            }

            $this->table(
                ['State', 'Count', 'Total Amount'],
                collect($reportData)->map(function ($data, $stateId) {
                    return [
                        $data['name'],
                        $data['count'],
                        number_format($data['total'], 2)
                    ];
                })->toArray()
            );
        }

        $this->line('');
        $this->info("Grand Total: " . number_format($grandTotal, 2));
        $this->info("Total Invoices: " . $invoices->count());

        return 0;
    }
}