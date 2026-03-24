<?php

namespace App\Ai\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;

class ReceiptScanner implements Agent, HasStructuredOutput
{
    use Promptable;

    public function instructions(): string
    {
        return <<<'PROMPT'
You are a receipt/invoice data extractor. Analyze the uploaded receipt image or PDF and extract the following fields.

Rules:
- title: A concise description of what was purchased or the vendor name (e.g. "Office supplies - Digitec", "Train ticket Zurich-Bern"). Keep it short.
  - EXCEPTION for restaurants/cafés/bars/food establishments: Use "Business Lunch" if the time on the receipt is before 17:00 (or no time is available), or "Business Dinner" if 17:00 or later.
- date: The date on the receipt in Y-m-d format (e.g. "2026-03-23"). If unclear, use today's date.
- description: A brief summary of the items/services on the receipt. Include vendor name, individual items if visible, and any reference numbers.
  - EXCEPTION for restaurants/cafés/bars/food establishments: Use the format "Business Lunch – [Restaurant Name]" or "Business Dinner – [Restaurant Name]" (matching the title).
- amount: The total amount as a decimal number (e.g. 42.50). Use the final total, not subtotals.
- currency: The 3-letter currency code (CHF, EUR, or USD). Default to CHF if unclear.

Only return the structured data. Do not add commentary.
PROMPT;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'title' => $schema->string()->required(),
            'date' => $schema->string()->required(),
            'description' => $schema->string()->required(),
            'amount' => $schema->number()->required(),
            'currency' => $schema->string()->enum(['CHF', 'EUR', 'USD'])->required(),
        ];
    }
}
