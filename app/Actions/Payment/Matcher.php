<?php

namespace App\Actions\Payment;

use App\Models\Invoice;
use App\Models\InvoiceState;
use Illuminate\Support\Collection;

/**
 * Pairs bank credits with invoices.
 *
 * An invoice number in the payment's reference — plain ("26.0762") or as the
 * QR bill's creditor reference ("RF…") — is the strong signal. Without one, a
 * payment is only suggested when exactly one unpaid invoice has that amount,
 * with the payer's name breaking ties between same-amount invoices.
 */
class Matcher
{
    // Statuses, from most to least trustworthy
    const MATCHED = 'matched';            // number found, amount agrees, unpaid
    const SUGGESTED = 'suggested';        // no number, unique by amount (and name)
    const AMOUNT_MISMATCH = 'amount_mismatch';
    const ALREADY_PAID = 'already_paid';
    const UNMATCHED = 'unmatched';

    const UNPAID_STATES = [InvoiceState::DRAFT, InvoiceState::PENDING];

    /**
     * @param  array<int, array{date: string, amount: float|string, payer: string, reference: string}>  $payments
     */
    public function execute(array $payments): array
    {
        $unpaid = Invoice::with('client')
            ->whereIn('state_id', self::UNPAID_STATES)
            ->get();

        $claimed = [];

        // Referenced payments first, so a guess by amount never takes an
        // invoice that a later payment names explicitly.
        $results = [];
        $pending = [];

        foreach (array_values($payments) as $i => $payment) {
            $payment = [
                'date' => (string) ($payment['date'] ?? ''),
                'amount' => round((float) ($payment['amount'] ?? 0), 2),
                'payer' => trim((string) ($payment['payer'] ?? '')),
                'reference' => trim((string) ($payment['reference'] ?? '')),
            ];

            $invoice = $this->byNumber($payment['reference']);

            if (!$invoice) {
                $pending[$i] = $payment;
                continue;
            }

            $results[$i] = $this->result($payment, $invoice, $this->statusFor($payment, $invoice, $claimed));
            if ($results[$i]['status'] === self::MATCHED) {
                $claimed[] = $invoice->id;
            }
        }

        foreach ($pending as $i => $payment) {
            $invoice = $this->byAmount($payment, $unpaid->whereNotIn('id', $claimed));

            if ($invoice) {
                $claimed[] = $invoice->id;
            }

            $results[$i] = $this->result($payment, $invoice, $invoice ? self::SUGGESTED : self::UNMATCHED);
        }

        ksort($results);

        return array_values($results);
    }

    /**
     * Invoice numbers mentioned in a reference, e.g. ['26.0762'].
     */
    public function numbers(string $reference): array
    {
        $numbers = [];

        // "26.0762" — but not the "09.2026" inside a date like "18.09.2026"
        preg_match_all('/(?<![\d.])(\d{2})\.(\d{4})(?![\d.])/', $reference, $m, PREG_SET_ORDER);
        foreach ($m as $match) {
            $numbers[] = "{$match[1]}.{$match[2]}";
        }

        // Creditor reference "RF23 2605 97" → 25.0597 (see Invoice\QrBill)
        preg_match_all('/RF\d{2}(\d{6})(?!\d)/i', preg_replace('/\s+/', '', $reference), $m, PREG_SET_ORDER);
        foreach ($m as $match) {
            $numbers[] = substr($match[1], 0, 2) . '.' . substr($match[1], 2);
        }

        return array_values(array_unique($numbers));
    }

    protected function byNumber(string $reference): ?Invoice
    {
        $numbers = $this->numbers($reference);

        if (!$numbers) {
            return null;
        }

        return Invoice::with('client')->whereIn('number', $numbers)->first();
    }

    protected function statusFor(array $payment, Invoice $invoice, array $claimed): string
    {
        if ($invoice->isPaid() || in_array($invoice->id, $claimed)) {
            return self::ALREADY_PAID;
        }

        if ($invoice->isCancelled()) {
            return self::UNMATCHED;
        }

        return $this->sameAmount($payment['amount'], $invoice) ? self::MATCHED : self::AMOUNT_MISMATCH;
    }

    protected function byAmount(array $payment, Collection $unpaid): ?Invoice
    {
        $candidates = $unpaid->filter(fn (Invoice $invoice) => $this->sameAmount($payment['amount'], $invoice));

        if ($candidates->count() > 1) {
            $candidates = $candidates->filter(fn (Invoice $invoice) => $this->sameName($payment['payer'], $invoice));
        }

        return $candidates->count() === 1 ? $candidates->first() : null;
    }

    protected function sameAmount(float $amount, Invoice $invoice): bool
    {
        return abs($amount - round((float) $invoice->grandtotal, 2)) < 0.005;
    }

    /**
     * Payer and client share a significant word ("Stoz Werbeagentur AG" ~ "Stoz").
     */
    protected function sameName(string $payer, Invoice $invoice): bool
    {
        $words = fn (?string $name) => array_filter(
            preg_split('/[^\p{L}\p{N}]+/u', mb_strtolower((string) $name)),
            fn ($word) => mb_strlen($word) >= 3 && !in_array($word, ['ag', 'gmbh', 'und', 'the'])
        );

        $client = array_merge($words($invoice->client?->name), $words($invoice->client?->acronym));

        return (bool) array_intersect($words($payer), $client);
    }

    protected function result(array $payment, ?Invoice $invoice, string $status): array
    {
        return array_merge($payment, [
            'status' => $status,
            'invoice' => $invoice ? [
                'id' => $invoice->id,
                'number' => $invoice->number,
                'title' => $invoice->title,
                'client' => $invoice->client?->name,
                'grandtotal' => round((float) $invoice->grandtotal, 2),
                'state_id' => $invoice->state_id,
            ] : null,
        ]);
    }
}
