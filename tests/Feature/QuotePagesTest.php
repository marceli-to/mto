<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Quote;
use App\Models\QuoteBlock;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class QuotePagesTest extends TestCase
{
    use RefreshDatabase;

    private Client $client;

    protected function setUp(): void
    {
        parent::setUp();
        Sanctum::actingAs(User::forceCreate([
            'name' => 'Test',
            'email' => 'test@example.com',
            'password' => bcrypt('secret'),
        ]));
        $this->client = Client::create(['name' => 'Acme', 'zip' => '8000', 'city' => 'Zürich']);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'label' => 'Offerte',
            'title' => 'Relaunch',
            'date' => '2026-10-30',
            'client_id' => $this->client->id,
            'vat_rate' => 8.1,
            'pages' => [
                ['type' => 'text', 'content' => '<h2>Ausgangslage</h2><p>Text</p>'],
                ['type' => 'prices', 'content' => '<h2>Preise</h2>'],
                ['type' => 'text', 'content' => '<h2>Konditionen</h2>'],
            ],
            'sections' => [
                ['title' => 'Grundleistungen', 'show_total' => false, 'show_vat_note' => false, 'positions' => [
                    ['description' => 'Relaunch', 'amount' => 24000],
                ]],
                ['title' => 'Optionen', 'show_total' => true, 'show_vat_note' => true, 'positions' => [
                    ['description' => 'Abstimmungsmodul', 'amount' => 3600],
                    ['description' => 'M+-API', 'amount' => 999, 'amount_text' => 'Fixpreis folgt'],
                ]],
                ['title' => 'Lizenzgebühren', 'currency' => 'USD', 'show_total' => false, 'show_vat_note' => false, 'positions' => [
                    ['description' => 'Statamic Pro', 'amount' => 349],
                ]],
            ],
        ], $overrides);
    }

    public function test_creates_a_quote_with_ordered_pages_and_section_options(): void
    {
        $id = $this->postJson('/api/quote/create', $this->payload())->assertOk()->json('quoteId');

        $quote = Quote::with('pages', 'sections.positions')->find($id);

        $this->assertSame('Offerte', $quote->label);
        $this->assertSame(['text', 'prices', 'text'], $quote->pages->pluck('type')->all());
        $this->assertFalse($quote->sections[0]->show_total);
        $this->assertSame('USD', $quote->sections[2]->currency);
        $this->assertSame('Fixpreis folgt', $quote->sections[1]->positions[1]->amount_text);
        // A position with a text instead of an amount doesn't count towards the total
        $this->assertEquals(3600, $quote->sections[1]->total);
    }

    public function test_update_reorders_pages_and_removes_missing_ones(): void
    {
        $id = $this->postJson('/api/quote/create', $this->payload())->json('quoteId');
        $pages = Quote::find($id)->pages;

        $this->postJson("/api/quote/update/{$id}", $this->payload([
            'pages' => [
                ['id' => $pages[1]->id, 'type' => 'prices', 'content' => null],
                ['id' => $pages[0]->id, 'type' => 'text', 'content' => '<p>Geändert</p>'],
            ],
        ]))->assertOk();

        $pages = Quote::find($id)->pages;
        $this->assertCount(2, $pages);
        $this->assertSame(['prices', 'text'], $pages->pluck('type')->all());
        $this->assertSame('<p>Geändert</p>', $pages[1]->content);
    }

    public function test_duplicate_copies_pages_and_section_options(): void
    {
        $id = $this->postJson('/api/quote/create', $this->payload())->json('quoteId');

        $cloneId = $this->getJson("/api/quote/duplicate/{$id}")->assertOk()->json('id');

        $clone = Quote::with('pages', 'sections.positions')->find($cloneId);
        $this->assertSame(['text', 'prices', 'text'], $clone->pages->pluck('type')->all());
        $this->assertSame('USD', $clone->sections[2]->currency);
        $this->assertSame('Fixpreis folgt', $clone->sections[1]->positions[1]->amount_text);
    }

    public function test_pdf_view_renders_pages_in_order_with_price_options(): void
    {
        $id = $this->postJson('/api/quote/create', $this->payload())->json('quoteId');
        // The date mutator writes "Y.m.d", which MySQL normalises but SQLite keeps as is
        Quote::whereKey($id)->toBase()->update(['date' => '2026-10-30']);
        $quote = Quote::with('pages', 'sections.positions', 'client')->find($id);

        $html = view('pdf.quote', ['quote' => $quote])->render();

        $this->assertLessThan(strpos($html, 'Preise'), strpos($html, 'Ausgangslage'));
        $this->assertLessThan(strpos($html, 'Konditionen'), strpos($html, 'Preise'));
        $this->assertStringContainsString('Fixpreis folgt', $html);
        $this->assertStringContainsString("USD 349.00", $html);
        $this->assertStringContainsString("24\u{202F}000.00", $html);
        // Only "Optionen" has a total row and a VAT note
        $this->assertSame(1, substr_count($html, 'class="total-row"'));
        $this->assertSame(1, substr_count($html, 'exklusive 8.1% MwSt.'));
    }

    public function test_manages_text_blocks(): void
    {
        $block = $this->postJson('/api/quote-block/create', [
            'title' => 'Hosting',
            'content' => '<h2>Anforderungen Hosting</h2>',
            'is_default' => true,
        ])->assertOk()->json();

        $this->postJson("/api/quote-block/update/{$block['id']}", ['title' => 'Hosting neu', 'content' => '<p>x</p>', 'is_default' => false])
            ->assertOk();
        $this->assertSame('Hosting neu', QuoteBlock::find($block['id'])->title);

        $this->getJson('/api/quote-blocks/get')->assertOk()->assertJsonFragment(['title' => 'Hosting neu']);

        $this->deleteJson("/api/quote-block/destroy/{$block['id']}")->assertOk();
        $this->assertNull(QuoteBlock::find($block['id']));
    }

    public function test_requires_a_label(): void
    {
        $this->postJson('/api/quote/create', $this->payload(['label' => '']))
            ->assertStatus(422)
            ->assertJsonValidationErrors('label');
    }
}
