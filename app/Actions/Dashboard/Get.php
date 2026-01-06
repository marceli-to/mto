<?php

namespace App\Actions\Dashboard;

use App\Models\Invoice;
use App\Models\Expense;
use App\Models\Client;
use App\Models\Project;
use App\Models\InvoiceState;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class Get
{
    private int $currentYear;
    private Collection $invoices;
    private Collection $expenses;

    public function execute()
    {
        $this->currentYear = Carbon::now()->year;
        $this->invoices = Invoice::with('client', 'state')->get();
        $this->expenses = Expense::all();

        return response()->json([
            'invoices' => $this->getInvoiceStats(),
            'expenses' => $this->getExpenseStats(),
            'profit' => $this->getProfitStats(),
            'clients' => $this->getClientStats(),
            'projects' => $this->getProjectStats(),
            'yearlyRankings' => $this->getYearlyRankings(),
            'year' => $this->currentYear,
        ]);
    }

    private function getInvoiceStats(): array
    {
        $states = InvoiceState::all();
        
        $totals = [];
        foreach ($states as $state) {
            $totals[$state->description] = $this->invoices
                ->where('state_id', $state->id)
                ->sum('grandtotal');
        }
        
        $activeInvoices = $this->invoices->reject->isCancelled();
        $totals['total'] = $activeInvoices->sum('grandtotal');

        $count = $activeInvoices->count();
        $average = $count > 0 ? $totals['total'] / $count : 0;

        $thisYearInvoices = $activeInvoices->filter(
            fn($inv) => Carbon::parse($inv->date)->year === $this->currentYear
        );

        return [
            'totals' => $totals,
            'count' => $count,
            'average' => round($average, 2),
            'thisYear' => [
                'revenue' => $thisYearInvoices->sum('grandtotal'),
                'count' => $thisYearInvoices->count(),
                'paid' => $thisYearInvoices->filter->isPaid()->sum('grandtotal')
                        + $thisYearInvoices->filter->isPending()->sum('grandtotal'),
                'net' => $thisYearInvoices->filter->isPaid()->sum('grandtotal')
                       + $thisYearInvoices->filter->isPending()->sum('grandtotal')
                       - $this->getThisYearExpenses(),
            ],
        ];
    }

    private function getExpenseStats(): array
    {
        return [
            'total' => $this->expenses->sum('amount'),
            'count' => $this->expenses->count(),
            'thisYear' => $this->getThisYearExpenses(),
        ];
    }

    private function getThisYearExpenses(): float
    {
        return $this->expenses
            ->filter(fn($exp) => Carbon::parse($exp->date)->year === $this->currentYear)
            ->sum('amount');
    }

    private function getProfitStats(): array
    {
        $paidRevenue = $this->invoices->filter->isPaid()->sum('grandtotal');
        $totalExpenses = $this->expenses->sum('amount');
        $netProfit = $paidRevenue - $totalExpenses;

        return [
            'net' => $netProfit,
            'margin' => $paidRevenue > 0 ? round(($netProfit / $paidRevenue) * 100, 1) : 0,
        ];
    }

    private function getClientStats(): array
    {
        $topClients = $this->invoices
            ->filter->isPaid()
            ->groupBy('client_id')
            ->map(function ($clientInvoices) {
                $client = $clientInvoices->first()->client;
                return [
                    'id' => $client?->id,
                    'name' => $client?->name ?? 'Unknown',
                    'acronym' => $client?->acronym ?? '?',
                    'total' => $clientInvoices->sum('grandtotal'),
                    'count' => $clientInvoices->count(),
                ];
            })
            ->sortByDesc('total')
            ->take(7)
            ->values();

        return [
            'count' => Client::count(),
            'top' => $topClients,
        ];
    }

    private function getProjectStats(): array
    {
        $projects = Project::all();

        return [
            'active' => $projects->where('is_archive', false)->count(),
            'archived' => $projects->where('is_archive', true)->count(),
            'total' => $projects->count(),
        ];
    }

    private function getYearlyRankings(): Collection
    {
        $minYear = $this->invoices->min(fn($inv) => Carbon::parse($inv->date)->year) 
                 ?? $this->currentYear;

        $yearlyProfits = collect();

        for ($year = $this->currentYear; $year >= $minYear; $year--) {
            [$fiscalStart, $fiscalEnd] = Invoice::fiscalYearRange($year);
            
            $yearRevenue = $this->calculateYearRevenue($year, $fiscalStart, $fiscalEnd);
            $yearExpenses = $this->calculateYearExpenses($year);
            
            $yearlyProfits->push([
                'year' => $year,
                'revenue' => $yearRevenue,
                'expenses' => $yearExpenses,
                'net' => $yearRevenue - $yearExpenses,
            ]);
        }

        return $yearlyProfits
            ->sortByDesc('net')
            ->values()
            ->map(function ($item, $index) {
                $item['rank'] = $index + 1;
                return $item;
            })
            ->values();
    }

    private function calculateYearRevenue(int $year, Carbon $fiscalStart, Carbon $fiscalEnd): float
    {
        return $this->invoices
            ->filter(function ($inv) use ($year, $fiscalStart, $fiscalEnd) {
                if ($inv->isPending()) {
                    return Carbon::parse($inv->date)->year === $year;
                }
                if ($inv->isPaid()) {
                    $paidDate = Carbon::parse($inv->date_paid ?? $inv->date);
                    return $paidDate->between($fiscalStart, $fiscalEnd);
                }
                return false;
            })
            ->sum('grandtotal');
    }

    private function calculateYearExpenses(int $year): float
    {
        return $this->expenses
            ->filter(fn($exp) => Carbon::parse($exp->date)->year === $year)
            ->sum('amount');
    }
}
