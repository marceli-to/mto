<?php

namespace Tests\Feature;

use App\Actions\Invoice\QrBill;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceState;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceQrBillTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // invoices.state_id carries a FK to states, so the ones used here
        // have to exist.
        foreach ([InvoiceState::DRAFT => 'open', InvoiceState::CANCELLED => 'cancelled'] as $id => $description) {
            if (!InvoiceState::find($id)) {
                InvoiceState::forceCreate(['id' => $id, 'description' => $description]);
            }
        }
    }

    private function actAsUser(): void
    {
        $this->actingAs(User::forceCreate([
            'name' => 'Test',
            'email' => 'test@example.com',
            'password' => bcrypt('secret'),
        ]));
    }

    private function invoice(array $attributes = []): Invoice
    {
        $client = Client::create([
            'name' => 'Acme AG',
            'acronym' => 'ACM',
            'street' => 'Musterstrasse 1',
            'zip' => '8000',
            'city' => 'Zürich',
        ]);

        return Invoice::create(array_merge([
            'number' => '25.0597',
            'title' => 'Sammelauftrag',
            'total' => 100.00,
            'vat' => 8.10,
            'vat_rate' => 8.1,
            'grandtotal' => 108.10,
            'date' => '2025-07-08',
            'date_due' => '2025-07-29',
            'client_id' => $client->id,
            'state_id' => InvoiceState::DRAFT,
        ], $attributes));
    }

    public function test_it_builds_a_valid_payment_part(): void
    {
        $part = (new QrBill)->execute($this->invoice());

        $this->assertNotNull($part);
        $this->assertStringContainsString('qr-bill', $part);
        $this->assertStringContainsString('Empfangsschein', $part);
        // The amount and account have to reach the client, so assert they render.
        $this->assertStringContainsString('108.10', $part);
        $this->assertStringContainsString('CH22 8080 8003 1865 2284 6', $part);
    }

    public function test_the_reference_is_a_valid_iso_11649_creditor_reference(): void
    {
        $reference = (new QrBill)->reference($this->invoice());

        $this->assertSame('RF23250597', $reference);
        $this->assertMatchesRegularExpression('/^RF\d{2}[0-9A-Z]+$/', $reference);

        // Moving RFxx to the end and reading the result as a number must leave
        // remainder 1 — that is what makes the check digits valid.
        $rearranged = substr($reference, 4) . substr($reference, 0, 4);
        $numeric = '';
        foreach (str_split($rearranged) as $character) {
            $numeric .= ctype_alpha($character) ? (string) (ord($character) - 55) : $character;
        }
        $remainder = 0;
        foreach (str_split($numeric) as $digit) {
            $remainder = ($remainder * 10 + (int) $digit) % 97;
        }
        $this->assertSame(1, $remainder);
    }

    public function test_the_reference_is_stable_for_the_same_invoice(): void
    {
        $invoice = $this->invoice();
        $action = new QrBill;

        $this->assertSame($action->reference($invoice), $action->reference($invoice->fresh()));
    }

    public function test_cancelled_invoices_get_no_payment_part(): void
    {
        $invoice = $this->invoice(['state_id' => InvoiceState::CANCELLED]);
        $action = new QrBill;

        $this->assertFalse($action->isPayable($invoice));
        $this->assertNull($action->execute($invoice));
    }

    public function test_zero_total_invoices_get_no_payment_part(): void
    {
        $invoice = $this->invoice(['total' => 0, 'vat' => 0, 'grandtotal' => 0]);

        $this->assertNull((new QrBill)->execute($invoice));
    }

    public function test_a_reminder_is_labelled_as_one(): void
    {
        $invoice = $this->invoice(['is_reminder' => true, 'reminder_level' => 2]);

        $this->assertStringContainsString('2. Mahnung', (new QrBill)->execute($invoice));
    }

    public function test_long_titles_are_capped_to_the_standards_limit(): void
    {
        $invoice = $this->invoice(['title' => str_repeat('Sehr langer Projekttitel ', 20)]);

        // Over the 140 character limit the bill would fail validation, so the
        // action has to truncate rather than throw.
        $this->assertNotNull((new QrBill)->execute($invoice));
    }

    public function test_the_preview_route_renders_the_payment_part(): void
    {
        $invoice = $this->invoice();

        $this->actAsUser();

        $this->get("/invoice/qr/{$invoice->id}")
            ->assertOk()
            ->assertSee('Empfangsschein', false);
    }

    public function test_the_preview_route_404s_for_a_cancelled_invoice(): void
    {
        $invoice = $this->invoice(['state_id' => InvoiceState::CANCELLED]);

        $this->actAsUser();

        $this->get("/invoice/qr/{$invoice->id}")->assertNotFound();
    }
}
