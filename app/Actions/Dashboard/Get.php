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

        // Recent activity
        $recentInvoices = Invoice::with('client', 'state')
            ->orderBy('created_at', 'DESC')
            ->take(5)
            ->get()
            ->map(function ($inv) {
                return [
                    'id' => $inv->id,
                    'number' => $inv->number,
                    'title' => $inv->title,
                    'client' => $inv->client?->acronym,
                    'amount' => $inv->grandtotal,
                    'state' => $inv->state?->description,
                    'date' => $inv->date
                ];
            });

        $recentExpenses = Expense::orderBy('created_at', 'DESC')
            ->take(5)
            ->get()
            ->map(function ($exp) {
                return [
                    'id' => $exp->id,
                    'title' => $exp->title,
                    'amount' => $exp->amount,
                    'date' => $exp->date
                ];
            });

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
            'recent' => [
                'invoices' => $recentInvoices,
                'expenses' => $recentExpenses
            ],
            'year' => $currentYear
        ]);
    }
}
