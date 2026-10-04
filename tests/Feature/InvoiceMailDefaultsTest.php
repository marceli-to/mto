<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Contact;
use App\Models\Invoice;
use App\Models\InvoiceState;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class InvoiceMailDefaultsTest extends TestCase
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

        // invoices.state_id carries a FK to states.
        if (!InvoiceState::find(InvoiceState::DRAFT)) {
            InvoiceState::forceCreate(['id' => InvoiceState::DRAFT, 'description' => 'open']);
        }
    }

    private function invoiceFor(Client $client): Invoice
    {
        return Invoice::create([
            'number' => '26.0774',
            'title' => 'Website',
            'total' => 100,
            'vat' => 8.10,
            'vat_rate' => 8.1,
            'grandtotal' => 108.10,
            'date' => '2026-10-04',
            'date_due' => '2026-11-03',
            'client_id' => $client->id,
            'state_id' => InvoiceState::DRAFT,
        ]);
    }

    public function test_the_billing_email_is_the_default_recipient(): void
    {
        $client = Client::create(['name' => 'Acme', 'acronym' => 'ACM', 'billing_email' => 'invoices@acme.ch']);
        Contact::create(['client_id' => $client->id, 'firstname' => 'Anna', 'name' => 'Muster', 'email' => 'anna@acme.ch']);

        $this->getJson("/api/invoice/mail/{$this->invoiceFor($client)->id}")
            ->assertOk()
            ->assertJsonPath('to', 'invoices@acme.ch');
    }

    public function test_it_falls_back_to_the_first_contact_with_an_email(): void
    {
        $client = Client::create(['name' => 'Acme', 'acronym' => 'ACM']);
        Contact::create(['client_id' => $client->id, 'firstname' => 'Anna', 'name' => 'Muster', 'email' => 'anna@acme.ch']);

        $this->getJson("/api/invoice/mail/{$this->invoiceFor($client)->id}")
            ->assertOk()
            ->assertJsonPath('to', 'anna@acme.ch');
    }

    public function test_the_recipient_is_empty_without_billing_email_or_contacts(): void
    {
        $client = Client::create(['name' => 'Acme', 'acronym' => 'ACM']);

        $this->getJson("/api/invoice/mail/{$this->invoiceFor($client)->id}")
            ->assertOk()
            ->assertJsonPath('to', '');
    }

    public function test_the_billing_email_is_saved_and_validated(): void
    {
        $this->postJson('/api/client/create', ['name' => 'Acme', 'billing_email' => 'not-an-email'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('billing_email');

        $this->postJson('/api/client/create', ['name' => 'Acme', 'billing_email' => 'invoices@acme.ch'])
            ->assertOk();

        $this->assertDatabaseHas('clients', ['name' => 'Acme', 'billing_email' => 'invoices@acme.ch']);
    }
}
