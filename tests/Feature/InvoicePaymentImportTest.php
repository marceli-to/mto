<?php

namespace Tests\Feature;

use App\Actions\Payment\Matcher;
use App\Ai\Agents\StatementScanner;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceState;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class InvoicePaymentImportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Sanctum::actingAs(User::forceCreate([
            'name' => 'Test',
            'email' => 'test@example.com',
            'password' => bcrypt('secret'),
        ]));

        foreach ([1 => 'open', 2 => 'pending', 3 => 'paid', 6 => 'cancelled'] as $id => $description) {
            InvoiceState::forceCreate(['id' => $id, 'description' => $description]);
        }
    }

    private function invoice(string $number, float $grandtotal, string $client, int $stateId = InvoiceState::PENDING): Invoice
    {
        $invoice = Invoice::create([
            'title' => 'Test Invoice',
            'date' => '2026-09-18',
            'date_due' => '2026-10-18',
            'client_id' => Client::firstOrCreate(['name' => $client])->id,
            'state_id' => $stateId,
            'vat_rate' => 8.1,
            'total' => $grandtotal,
            'vat' => 0,
            'grandtotal' => $grandtotal,
        ]);
        $invoice->number = $number;
        $invoice->save();

        return $invoice;
    }

    private function payment(float $amount, string $payer, string $reference = '', string $date = '2026-10-02'): array
    {
        return compact('date', 'amount', 'payer', 'reference');
    }

    public function test_finds_invoice_numbers_but_not_dates(): void
    {
        $matcher = new Matcher;

        $this->assertSame(['26.0757'], $matcher->numbers('18.09.2026 26.0757'));
        $this->assertSame(['25.0597'], $matcher->numbers('RF23 2505 97'));
        $this->assertSame([], $matcher->numbers('Bezahlt für: SIPT 22.09.2026'));
    }

    public function test_matches_by_number_and_checks_the_amount(): void
    {
        $this->invoice('26.0762', 227.05, 'Stoz Werbeagentur AG');
        $this->invoice('26.0768', 900.00, 'Stoz Werbeagentur AG');
        $this->invoice('26.0754', 2459.30, 'Stoz Werbeagentur AG', InvoiceState::PAID);

        $results = (new Matcher)->execute([
            $this->payment(227.05, 'Stoz Werbeagentur AG', '26.0762'),
            $this->payment(870.25, 'Stoz Werbeagentur AG', '26.0768'),
            $this->payment(2459.30, 'Stoz Werbeagentur AG', '26.0754'),
            $this->payment(100.00, 'Stoz Werbeagentur AG', '26.9999'),
        ]);

        $this->assertSame(
            [Matcher::MATCHED, Matcher::AMOUNT_MISMATCH, Matcher::ALREADY_PAID, Matcher::UNMATCHED],
            array_column($results, 'status')
        );
        $this->assertSame('26.0762', $results[0]['invoice']['number']);
    }

    public function test_suggests_by_amount_using_the_payer_to_break_ties(): void
    {
        $this->invoice('26.0750', 908.05, 'SIPT Schweizer Institut f.Psychotraumatologie GmbH');
        $this->invoice('26.0751', 567.55, 'Marco Barberi');
        $this->invoice('26.0752', 567.55, 'nimeg ag');
        $this->invoice('26.0753', 567.55, 'Baustoff Kreislauf Schweiz');

        $results = (new Matcher)->execute([
            $this->payment(908.05, 'Schweizer Institut für Psychotraumatologie (SIPT) GmbH', 'Bezahlt für: SIPT 22.09.2026'),
            $this->payment(567.55, 'Barberi Marco'),
            $this->payment(567.55, 'Someone Else'),
            $this->payment(42.00, 'Nobody'),
        ]);

        $this->assertSame([Matcher::SUGGESTED, Matcher::SUGGESTED, Matcher::UNMATCHED, Matcher::UNMATCHED], array_column($results, 'status'));
        $this->assertSame('26.0750', $results[0]['invoice']['number']);
        $this->assertSame('26.0751', $results[1]['invoice']['number']);
    }

    public function test_a_referenced_invoice_is_not_also_guessed_for_another_payment(): void
    {
        $this->invoice('26.0765', 567.55, 'nimeg ag');

        $results = (new Matcher)->execute([
            $this->payment(567.55, 'nimeg ag'),
            $this->payment(567.55, 'nimeg ag', '26.0765'),
        ]);

        $this->assertSame([Matcher::UNMATCHED, Matcher::MATCHED], array_column($results, 'status'));
    }

    public function test_scan_returns_matched_payments_from_the_statement(): void
    {
        Storage::fake();
        Storage::put('public/temp/statement.pdf', '%PDF-1.4');
        $this->invoice('26.0760', 527.00, 'GATRA AG');

        StatementScanner::fake([[
            'payments' => [$this->payment(527.00, 'GATRA AG', '18.09.2026 26.0760', '2026-10-05')],
        ]]);

        $res = $this->postJson('/api/invoices/payments/scan', ['temp_file' => 'statement.pdf']);

        $res->assertOk()
            ->assertJsonPath('payments.0.status', Matcher::MATCHED)
            ->assertJsonPath('payments.0.invoice.number', '26.0760');
    }

    public function test_apply_marks_invoices_paid_on_their_own_dates(): void
    {
        $a = $this->invoice('26.0762', 227.05, 'Stoz');
        $b = $this->invoice('26.0760', 527.00, 'GATRA AG');
        $paid = $this->invoice('26.0754', 2459.30, 'Stoz', InvoiceState::PAID);

        $res = $this->postJson('/api/invoices/payments/apply', ['payments' => [
            ['invoice_id' => $a->id, 'date_paid' => '2026-10-02'],
            ['invoice_id' => $b->id, 'date_paid' => '2026-10-05'],
            ['invoice_id' => $paid->id, 'date_paid' => '2026-10-02'],
        ]]);

        $res->assertOk();
        $this->assertEqualsCanonicalizing([$a->id, $b->id], $res->json('updated'));
        $this->assertSame(InvoiceState::PAID, $a->refresh()->state_id);
        $this->assertStringStartsWith('2026-10-05', str_replace('.', '-', $b->refresh()->getRawOriginal('date_paid')));
    }
}
