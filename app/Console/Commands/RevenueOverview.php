<?php

namespace App\Console\Commands;

use App\Models\InvoiceState;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RevenueOverview extends Command
{
    protected $signature = 'revenue:overview';

    protected $description = 'Monthly revenue per year against the revenue target';

    /** First and last calendar year of the report. */
    private const FIRST_YEAR = 2020;
    private const LAST_YEAR = 2026;

    /** Annual net revenue target in CHF, excl. MWST. */
    private const TARGET_YEAR = 143650.00;

    /**
     * The invoice date that drives revenue recognition. Flip to 'date_paid'
     * for a cash-basis view instead of the accrual view.
     */
    private const REVENUE_DATE = 'date';

    /**
     * States that count as revenue: everything that is not cancelled, so open
     * invoices are counted as issued rather than as drafts. Deliberately wider
     * than Invoice::billable().
     */
    private const REVENUE_STATES = [
        InvoiceState::DRAFT,
        InvoiceState::PENDING,
        InvoiceState::PAID,
        InvoiceState::CLOSED,
    ];

    public function handle()
    {
        $revenue = $this->revenueByMonth();
        $partial = false;
        $grandTotal = 0.0;
        $grandReached = 0;
        $grandElapsed = 0;
        $years = 0;
        $targetMonth = self::TARGET_YEAR / 12;
        $today = Carbon::today();

        $this->line('');
        $this->info('Revenue overview ' . self::FIRST_YEAR . ' - ' . self::LAST_YEAR);
        $this->line('Target   ' . $this->chf(self::TARGET_YEAR) . ' / year, ' . $this->chf($targetMonth) . ' / month');
        $this->line('Revenue  invoices.' . self::REVENUE_DATE . ', CHF net of MWST, ' . $this->stateLabel());

        $summary = [];

        for ($year = self::FIRST_YEAR; $year <= self::LAST_YEAR; $year++) {
            $rows = [];
            $total = 0.0;
            $reached = 0;
            $elapsed = 0;

            for ($month = 1; $month <= 12; $month++) {
                $key = sprintf('%04d-%02d', $year, $month);
                $label = Carbon::create($year, $month, 1)->format('F');

                // A month that has not happened yet is not a missed target.
                if (Carbon::create($year, $month, 1)->startOfMonth()->gt($today)) {
                    $rows[] = [$label, '-', '-', '-', '-'];
                    continue;
                }

                $net = $revenue[$key] ?? 0.0;
                $total += $net;

                // The current month is still running, so it counts towards the
                // year total but is not yet judged against the monthly target.
                if ($year === $today->year && $month === $today->month) {
                    $rows[] = [
                        $label,
                        $this->chf($net),
                        $this->chf($targetMonth),
                        $this->signed($net - $targetMonth),
                        'open',
                    ];
                    continue;
                }

                $elapsed++;
                $hit = $net >= $targetMonth;
                $reached += $hit ? 1 : 0;

                $rows[] = [
                    $label,
                    $this->chf($net),
                    $this->chf($targetMonth),
                    $this->signed($net - $targetMonth),
                    $hit ? 'yes' : 'no',
                ];
            }

            $rows[] = new \Symfony\Component\Console\Helper\TableSeparator();
            $rows[] = [
                'Total',
                $this->chf($total),
                $this->chf(self::TARGET_YEAR),
                $this->signed($total - self::TARGET_YEAR),
                $total >= self::TARGET_YEAR ? 'yes' : 'no',
            ];

            $this->line('');
            $this->info($year . ($elapsed < 12 ? '  (in progress, ' . $elapsed . ' of 12 months complete)' : ''));
            $this->table(['Month', 'Net revenue', 'Target', 'Delta', 'Goal'], $rows);
            $this->line('Months on target: ' . $reached . ' of ' . $elapsed . ' complete');

            $summary[] = [
                $year . ($elapsed < 12 ? ' *' : ''),
                $this->chf($total),
                $this->chf(self::TARGET_YEAR),
                $this->signed($total - self::TARGET_YEAR),
                $this->pct($total - self::TARGET_YEAR),
                $total >= self::TARGET_YEAR ? 'yes' : 'no',
                $reached . ' of ' . $elapsed,
            ];

            $partial = $partial || $elapsed < 12;
            $grandTotal += $total;
            $grandReached += $reached;
            $grandElapsed += $elapsed;
            $years++;
        }

        $grandTarget = self::TARGET_YEAR * $years;

        $summary[] = new \Symfony\Component\Console\Helper\TableSeparator();
        $summary[] = [
            'Total',
            $this->chf($grandTotal),
            $this->chf($grandTarget),
            $this->signed($grandTotal - $grandTarget),
            $grandTarget > 0 ? sprintf('%+.1f%%', ($grandTotal - $grandTarget) / $grandTarget * 100) : '-',
            $grandTotal >= $grandTarget ? 'yes' : 'no',
            $grandReached . ' of ' . $grandElapsed,
        ];

        $this->line('');
        $this->info('Results ' . self::FIRST_YEAR . ' - ' . self::LAST_YEAR);
        $this->table(['Year', 'Net revenue', 'Target', 'Delta', '%', 'Goal', 'Months on target'], $summary);

        if ($partial) {
            $this->line('* year not complete');
        }

        $this->line('');

        return 0;
    }

    /** Net revenue per calendar month across the reported years, aggregated in SQL. */
    private function revenueByMonth(): array
    {
        $column = 'invoices.' . self::REVENUE_DATE;

        return DB::table('invoices')
            ->whereNull('invoices.deleted_at')
            ->whereIn('invoices.state_id', self::REVENUE_STATES)
            ->whereNotNull($column)
            ->whereBetween($column, [
                Carbon::create(self::FIRST_YEAR, 1, 1)->toDateString(),
                Carbon::create(self::LAST_YEAR, 12, 31)->toDateString(),
            ])
            ->selectRaw("DATE_FORMAT({$column}, '%Y-%m') as ym, SUM(invoices.total) as net")
            ->groupBy('ym')
            ->orderBy('ym')
            ->get()
            ->keyBy('ym')
            ->map(fn ($row) => (float) $row->net)
            ->all();
    }

    /** Human-readable state list, read from the states table so it cannot drift. */
    private function stateLabel(): string
    {
        $counted = DB::table('states')->whereIn('id', self::REVENUE_STATES)->orderBy('id')->pluck('description');
        $excluded = DB::table('states')->whereNotIn('id', self::REVENUE_STATES)->orderBy('id')->pluck('description');

        return $counted->implode(', ') . ($excluded->isEmpty() ? '' : ' (' . $excluded->implode(', ') . ' excluded)');
    }

    /** Swiss thousands separator: 1'234.50 */
    private function chf(float $value): string
    {
        return number_format($value, 2, '.', "'");
    }

    private function signed(float $value): string
    {
        return ($value >= 0 ? '+' : '') . $this->chf($value);
    }

    /** Delta as a percentage of the annual target. */
    private function pct(float $delta): string
    {
        return sprintf('%+.1f%%', $delta / self::TARGET_YEAR * 100);
    }
}
