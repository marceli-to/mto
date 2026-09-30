<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceState;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class InvoiceStateBulkTest extends TestCase
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

    private function invoice(int $stateId = InvoiceState::DRAFT, ?string $datePaid = null): Invoice
    {
        $client = Client::create(['name' => 'Bill Co']);

        return Invoice::create([
            'title' => 'Test Invoice',
            'date' => '2026-09-01',
            'date_due' => '2026-09-30',
            'date_paid' => $datePaid,
            'client_id' => $client->id,
            'state_id' => $stateId,
            'vat_rate' => 8.1,
            'total' => 1000,
            'vat' => 81,
            'grandtotal' => 1081,
        ]);
    }

    public function test_sets_the_state_and_paid_date_on_every_invoice(): void
    {
        $a = $this->invoice(InvoiceState::PENDING);
        $b = $this->invoice(InvoiceState::PENDING);

        $res = $this->postJson('/api/invoices/update/state', [
            'state_id' => InvoiceState::PAID,
            'date_paid' => '2026-09-30',
            'invoice_ids' => [$a->id, $b->id],
        ]);

        $res->assertOk();
        $this->assertEqualsCanonicalizing([$a->id, $b->id], $res->json('updated'));
        foreach ([$a, $b] as $invoice) {
            $invoice->refresh();
            $this->assertEquals(InvoiceState::PAID, $invoice->state_id);
            $this->assertSame('2026-09-30', substr(str_replace('.', '-', $invoice->getRawOriginal('date_paid')), 0, 10));
        }
    }

    public function test_leaves_invoices_already_in_that_state_untouched(): void
    {
        $paid = $this->invoice(InvoiceState::PAID, '2026-08-15');

        $res = $this->postJson('/api/invoices/update/state', [
            'state_id' => InvoiceState::PAID,
            'date_paid' => '2026-09-30',
            'invoice_ids' => [$paid->id],
        ]);

        $res->assertOk();
        $this->assertSame([], $res->json('updated'));
        $this->assertSame('2026-08-15', substr(str_replace('.', '-', $paid->fresh()->getRawOriginal('date_paid')), 0, 10));
    }

    public function test_cancelling_zeroes_the_amounts(): void
    {
        $invoice = $this->invoice();

        $this->postJson('/api/invoices/update/state', [
            'state_id' => InvoiceState::CANCELLED,
            'invoice_ids' => [$invoice->id],
        ])->assertOk();

        $invoice->refresh();
        $this->assertEquals(InvoiceState::CANCELLED, $invoice->state_id);
        $this->assertEquals(0, $invoice->total);
        $this->assertEquals(0, $invoice->vat);
    }

    public function test_rejects_an_unknown_state(): void
    {
        $this->postJson('/api/invoices/update/state', [
            'state_id' => 99,
            'invoice_ids' => [$this->invoice()->id],
        ])->assertStatus(422);
    }
}
