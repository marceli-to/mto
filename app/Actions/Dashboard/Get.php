<?php

namespace App\Actions\Dashboard;

use App\Models\Invoice;
use App\Models\Expense;
use App\Models\Client;
use App\Models\Project;
use App\Models\InvoiceState;
use Carbon\Carbon;

class Get
{
    public function execute()
    {
        $currentYear = Carbon::now()->year;
        $currentMonth = Carbon::now()->month;

        // Invoice stats
        $invoices = Invoice::with('client', 'state')->get();
        $states = InvoiceState::all();
        
        $invoiceTotals = [];
        foreach ($states as $state) {
            $invoiceTotals[$state->description] = $invoices->where('state_id', $state->id)->sum('grandtotal');
        }
        $invoiceTotals['total'] = $invoices->where('state_id', '!=', 6)->sum('grandtotal');

        $invoiceCount = $invoices->where('state_id', '!=', 6)->count();
        $averageInvoice = $invoiceCount > 0 ? $invoiceTotals['total'] / $invoiceCount : 0;

        // This year's invoices (excluding cancelled)
        $thisYearInvoices = $invoices->filter(function ($inv) use ($currentYear) {
            return Carbon::parse($inv->date)->year === $currentYear && $inv->state_id != 6;
        });
        $thisYearRevenue = $thisYearInvoices->sum('grandtotal');
        $thisYearCount = $thisYearInvoices->count();

        // Expense stats
        $expenses = Expense::all();
        $totalExpenses = $expenses->sum('amount');
        $expenseCount = $expenses->count();

        $thisYearExpenses = $expenses->filter(function ($exp) use ($currentYear) {
            return Carbon::parse($exp->date)->year === $currentYear;
        });
        $thisYearExpenseTotal = $thisYearExpenses->sum('amount');

        // Net profit (paid invoices minus expenses)
        $paidRevenue = ($invoiceTotals['paid'] ?? 0) + ($invoiceTotals['closed'] ?? 0);
        $netProfit = $paidRevenue - $totalExpenses;
        $profitMargin = $paidRevenue > 0 ? ($netProfit / $paidRevenue) * 100 : 0;

        // This year's net
        $thisYearPaid = $thisYearInvoices->whereIn('state_id', [2, 3, 5])->sum('grandtotal'); // pending + paid + closed
        $thisYearNet = $thisYearPaid - $thisYearExpenseTotal;

        // Client stats
        $clients = Client::all();
        $clientCount = $clients->count();

        // Top clients by revenue (paid invoices)
        $topClients = $invoices
            ->whereIn('state_id', [3, 5]) // paid + closed
            ->groupBy('client_id')
            ->map(function ($clientInvoices) {
                $client = $clientInvoices->first()->client;
                return [
                    'id' => $client?->id,
                    'name' => $client?->name ?? 'Unknown',
                    'acronym' => $client?->acronym ?? '?',
                    'total' => $clientInvoices->sum('grandtotal'),
                    'count' => $clientInvoices->count()
                ];
            })
            ->sortByDesc('total')
            ->take(5)
            ->values();

        // Project stats
        $projects = Project::all();
        $activeProjects = $projects->where('is_archive', false)->count();
        $archivedProjects = $projects->where('is_archive', true)->count();

        // Yearly net profit rankings (last 5 years)
        // Fiscal year: Jan 26 of year to Jan 25 of year+1
        $yearlyProfits = collect();
        for ($year = $currentYear; $year >= $currentYear - 4; $year--) {
            $fiscalStart = Carbon::create($year, 1, 26)->startOfDay();
            $fiscalEnd = Carbon::create($year + 1, 1, 25)->endOfDay();
            
            $yearInvoices = $invoices->filter(function ($inv) use ($year, $fiscalStart, $fiscalEnd) {
                // Pending invoices: use invoice date
                if ($inv->state_id == 2) {
                    return Carbon::parse($inv->date)->year === $year;
                }
                // Paid/closed invoices: use date_paid within fiscal window
                if (in_array($inv->state_id, [3, 5]) && $inv->date_paid) {
                    $paidDate = Carbon::parse($inv->date_paid);
                    return $paidDate->between($fiscalStart, $fiscalEnd);
                }
                return false;
            });
            $yearRevenue = $yearInvoices->sum('grandtotal');
            
            $yearExpenses = $expenses->filter(function ($exp) use ($year) {
                return Carbon::parse($exp->date)->year === $year;
            })->sum('amount');
            
            $yearlyProfits->push([
                'year' => $year,
                'revenue' => $yearRevenue,
                'expenses' => $yearExpenses,
                'net' => $yearRevenue - $yearExpenses
            ]);
        }
        
        // Sort by net profit descending and add rank
        $yearlyRankings = $yearlyProfits->sortByDesc('net')->values()->map(function ($item, $index) {
            $item['rank'] = $index + 1;
            return $item;
        })->values();

        return response()->json([
            'invoices' => [
                'totals' => $invoiceTotals,
                'count' => $invoiceCount,
                'average' => round($averageInvoice, 2),
                'thisYear' => [
                    'revenue' => $thisYearRevenue,
                    'count' => $thisYearCount,
                    'paid' => $thisYearPaid,
                    'net' => $thisYearNet
                ]
            ],
            'expenses' => [
                'total' => $totalExpenses,
                'count' => $expenseCount,
                'thisYear' => $thisYearExpenseTotal
            ],
            'profit' => [
                'net' => $netProfit,
                'margin' => round($profitMargin, 1)
            ],
            'clients' => [
                'count' => $clientCount,
                'top' => $topClients
            ],
            'projects' => [
                'active' => $activeProjects,
                'archived' => $archivedProjects,
                'total' => $projects->count()
            ],
            'yearlyRankings' => $yearlyRankings,
            'year' => $currentYear
        ]);
    }
}
