<?php

namespace Tests\Feature;

use App\Actions\Invoice\QrBill;
use App\Actions\Pdf\Build;
use App\Models\Client;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\InvoicePosition;
use App\Models\InvoiceState;
use App\Models\Quote;
use App\Models\QuotePage;
use App\Models\QuotePosition;
use App\Models\QuoteSection;
use Fagus\Pdf;
use Fagus\Severity;
use Tests\TestCase;

/**
 * Renders every PDF view through Fagus with in-memory models: catches CSS the
 * engine doesn't understand and fonts it can't find, both of which it reports
 * as diagnostics instead of failing.
 */
class PdfRenderTest extends TestCase
{
    public function test_invoice_renders_with_qr_bill_on_its_own_page(): void
    {
        $invoice = $this->invoice(positions: 3);

        $pdf = $this->render('pdf.invoice', [
            'invoice' => $invoice,
            'qrBill' => (new QrBill)->execute($invoice),
        ]);

        // The invoice, then the QR bill alone on the last page
        $this->assertSame(2, $pdf->pages());
    }

    public function test_long_invoice_breaks_across_pages(): void
    {
        $invoice = $this->invoice(positions: 28);

        $pdf = $this->render('pdf.invoice', [
            'invoice' => $invoice,
            'qrBill' => (new QrBill)->execute($invoice),
        ]);

        $this->assertSame(4, $pdf->pages());
    }

    public function test_quote_renders_letter_and_pages(): void
    {
        $quote = new Quote(['label' => 'Offerte', 'title' => 'Relaunch', 'intro_greeting' => 'Hallo', 'intro_text' => '<p>Text</p>', 'vat_rate' => 8.1]);
        $quote->setRawAttributes(array_merge($quote->getAttributes(), ['date' => '2026-11-19']));
        $quote->setRelation('client', $this->client());
        $quote->setRelation('pages', collect([
            new QuotePage(['type' => QuotePage::TEXT, 'content' => '<h2>Ausgangslage</h2><ul><li>Punkt</li></ul>']),
            new QuotePage(['type' => QuotePage::PRICES, 'content' => '<h2>Preise</h2>']),
        ]));
        $section = new QuoteSection(['title' => 'Design', 'total_label' => 'Total', 'show_total' => true, 'show_vat_note' => true, 'currency' => 'CHF']);
        $section->setRelation('positions', collect([new QuotePosition(['description' => 'Entwurf', 'amount' => 1200])]));
        $quote->setRelation('sections', collect([$section]));

        $pdf = $this->render('pdf.quote', ['quote' => $quote]);

        // Letter, then one page per quote page
        $this->assertSame(3, $pdf->pages());
    }

    public function test_expense_renders_with_receipt_page(): void
    {
        $expense = new Expense(['number' => '26.0001', 'title' => 'Hosting', 'description' => 'Domain', 'currency' => 'CHF', 'amount' => 15]);
        $expense->setRawAttributes(array_merge($expense->getAttributes(), ['date' => '2026-03-08']));

        $pdf = $this->render('pdf.expense', ['expense' => $expense, 'receiptPages' => [$this->receiptImage()]]);

        $this->assertGreaterThanOrEqual(2, $pdf->pages());
    }

    public function test_expense_export_renders_overview_and_pages(): void
    {
        $expense = new Expense(['number' => '26.0001', 'title' => 'Hosting', 'description' => 'Domain', 'currency' => 'CHF', 'amount' => 15]);
        $expense->setRawAttributes(array_merge($expense->getAttributes(), ['date' => '2026-03-08']));

        $overview = $this->render('pdf.expense-export', [
            'overview' => true,
            'expenses' => collect([$expense]),
            'totals' => ['CHF' => ['count' => 1, 'amount' => 15]],
            'year' => 2026,
        ]);
        $pages = $this->render('pdf.expense-export', [
            'pages' => [['expense' => $expense, 'image' => $this->receiptImage(), 'pdf' => false]],
            'year' => 2026,
        ]);

        $this->assertSame(1, $overview->pages());
        $this->assertSame(1, $pages->pages());
    }

    private function render(string $view, array $data): Pdf
    {
        $pdf = (new Build)->execute($view, $data)->render();

        $this->assertStringStartsWith('%PDF-', $pdf->bytes());

        $problems = array_filter($pdf->diagnostics(), fn ($d) => $d->severity !== Severity::Info);
        $this->assertSame([], array_map('strval', array_values($problems)), "Fagus reported problems rendering {$view}");

        return $pdf;
    }

    private function invoice(int $positions): Invoice
    {
        $items = collect(range(1, $positions))->map(fn ($i) => new InvoicePosition([
            'periode' => '01.10.2026',
            'description' => "Anpassungen Website, Bugfixing und Abstimmung (Position $i)",
            'hours' => 2, 'rate' => 140, 'amount' => 280, 'is_flat' => false, 'is_fee' => false,
        ]));
        $total = 280 * $positions;
        $vat = ceil(($total / 100 * 8.1) * 20) / 20;

        $invoice = new Invoice([
            'number' => '26.0124', 'title' => 'Weiterentwicklung', 'total' => $total, 'vat' => $vat, 'vat_rate' => 8.1,
            'grandtotal' => $total + $vat, 'state_id' => InvoiceState::PENDING, 'is_reminder' => false,
        ]);
        // The date mutators write "Y.m.d", which only MySQL reads back as a date
        $invoice->setRawAttributes(array_merge($invoice->getAttributes(), ['date' => '2026-11-19', 'date_due' => '2026-12-19']));
        $invoice->setRelation('client', $this->client());
        $invoice->setRelation('positions', $items);

        return $invoice;
    }

    private function client(): Client
    {
        return new Client(['name' => 'Acme AG', 'acronym' => 'ACM', 'street' => 'Bahnhofstrasse 1', 'zip' => '8001', 'city' => 'Zürich']);
    }

    private function receiptImage(): string
    {
        $image = imagecreatetruecolor(400, 600);
        imagefill($image, 0, 0, imagecolorallocate($image, 240, 240, 240));
        ob_start();
        imagejpeg($image);

        return 'data:image/jpeg;base64,' . base64_encode(ob_get_clean());
    }
}
