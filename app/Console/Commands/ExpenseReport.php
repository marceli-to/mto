<?php

namespace App\Console\Commands;

use App\Models\Expense;
use Illuminate\Console\Command;
use Carbon\Carbon;

class ExpenseReport extends Command
{
    protected $signature = 'expense:report';

    protected $description = 'Generate an expense report for a specific period with totals by currency and optional CSV export';

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

        $this->info("Generating expense report from {$from} to {$to}");
        $this->line('');

        $expenses = Expense::whereBetween('date', [$fromDate->format('Y.m.d'), $toDate->format('Y.m.d')])
            ->orderBy('date', 'desc')
            ->get();

        if ($expenses->isEmpty()) {
            $this->info('No expenses found for the specified period');
            return 0;
        }

        // Group expenses by currency and calculate totals
        $currencyTotals = [];
        $grandTotal = 0;

        foreach ($expenses as $expense) {
            $currency = $expense->currency ?? 'CHF'; // Default to CHF if no currency
            
            if (!isset($currencyTotals[$currency])) {
                $currencyTotals[$currency] = [
                    'count' => 0,
                    'total' => 0
                ];
            }
            
            $currencyTotals[$currency]['count']++;
            $currencyTotals[$currency]['total'] += $expense->amount ?? 0;
            
            // For grand total, assume CHF if no currency specified
            if ($currency === 'CHF') {
                $grandTotal += $expense->amount ?? 0;
            }
        }

        // Display summary table
        $this->table(
            ['Currency', 'Count', 'Total Amount'],
            collect($currencyTotals)->map(function ($data, $currency) {
                return [
                    $currency,
                    $data['count'],
                    number_format($data['total'], 2)
                ];
            })->toArray()
        );

        $this->line('');
        $this->info("Grand Total (CHF): " . number_format($grandTotal, 2));
        $this->info("Total Expenses: " . $expenses->count());

        // Ask if user wants to export to CSV
        if ($this->confirm('Do you want to export the expenses to CSV?')) {
            $this->exportToCsv($expenses, $fromDate, $toDate);
        }

        // Ask if user wants to see detailed list
        if ($this->confirm('Do you want to see the detailed expense list?')) {
            $this->showDetailedList($expenses);
        }

        return 0;
    }

    private function exportToCsv($expenses, $fromDate, $toDate)
    {
        $filename = 'expenses_' . $fromDate->format('Y-m-d') . '_to_' . $toDate->format('Y-m-d') . '.csv';
        $filepath = storage_path('app/public/media/downloads/' . $filename);

        // Ensure directory exists
        $directory = dirname($filepath);
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $file = fopen($filepath, 'w');
        
        // CSV headers
        fputcsv($file, [
            'Number',
            'Date',
            'Title',
            'Description',
            'Amount',
            'Currency'
        ]);

        // CSV data
        foreach ($expenses as $expense) {
            fputcsv($file, [
                $expense->number ?? '',
                $expense->dateFormated,
                $expense->title,
                $expense->description ?? '',
                number_format($expense->amount ?? 0, 2),
                $expense->currency ?? 'CHF'
            ]);
        }

        fclose($file);

        $this->info("CSV exported to: {$filepath}");
        $this->info("Download URL: " . url('storage/media/downloads/' . $filename));
    }

    private function showDetailedList($expenses)
    {
        $this->line('');
        $this->info('Detailed Expense List:');
        $this->line('');

        $tableData = [];
        foreach ($expenses as $expense) {
            $tableData[] = [
                $expense->number ?? 'N/A',
                $expense->dateFormated,
                \Str::limit($expense->title, 30),
                number_format($expense->amount ?? 0, 2),
                $expense->currency ?? 'CHF'
            ];
        }

        $this->table(
            ['Number', 'Date', 'Title', 'Amount', 'Currency'],
            $tableData
        );
    }
}