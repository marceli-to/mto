<?php

namespace App\Ai\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;

class StatementScanner implements Agent, HasStructuredOutput
{
    use Promptable;

    public function instructions(): string
    {
        return <<<'PROMPT'
You are a bank statement data extractor. Analyze the uploaded bank statement (PDF) and list every incoming payment (credit, "Gutschrift").

Rules:
- Only include credits. Skip debits ("Belastung"), standing orders, transfers out, opening and closing balances.
- date: The booking date of the credit in Y-m-d format (e.g. "2026-10-02").
- amount: The credited amount as a decimal number (e.g. 2459.30). Swiss thousands separators (') are not part of the number.
- payer: The name of the payer as printed (company or person), without the address.
- reference: All remaining free text of the booking, verbatim and on one line — payment purpose, references (e.g. "RF23250597" or "26.0762"), dates, "Bezahlt für" notes. Empty string if there is none.

Only return the structured data. Do not add commentary.
PROMPT;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'payments' => $schema->array()->items(
                $schema->object([
                    'date' => $schema->string()->required(),
                    'amount' => $schema->number()->required(),
                    'payer' => $schema->string()->required(),
                    'reference' => $schema->string()->required(),
                ])->withoutAdditionalProperties()
            )->required(),
        ];
    }
}
