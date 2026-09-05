# AI-Powered Expense Creation from Receipts

## Summary

Upload a JPG or PDF receipt and have Claude extract expense fields automatically, pre-filling the existing expense form for review before saving.

## Flow

1. User clicks "Scan Receipt" on the expenses page
2. User selects a JPG or PDF file
3. Backend sends the file to Claude via Laravel AI SDK
4. Claude returns structured JSON: `{title, date, description, amount, currency}`
5. Frontend opens the existing ExpenseForm pre-filled with extracted data
6. User reviews, edits if needed, and saves via the normal flow

## Architecture

```
[Upload JPG/PDF] → [POST /api/expense/scan]
                         ↓
                  [ReceiptScanner Agent]
                  - Claude Sonnet (vision)
                  - Structured output
                  - Returns {title, date, description, amount, currency}
                         ↓
                  [JSON response to frontend]
                         ↓
                  [ExpenseForm pre-filled]
                         ↓
                  [User reviews → saves via existing flow]
```

## Backend Components

- **Package**: `laravel/ai` (Laravel AI SDK)
- **Agent**: `app/Ai/Agents/ReceiptScanner.php` — structured output agent
- **Controller**: New `scan()` method on `ExpenseController`
- **Route**: `POST /api/expense/scan`
- **Config**: `ANTHROPIC_API_KEY` in `.env`, `config/ai.php` published

## Frontend Components

- **ExpenseList.vue**: New "Scan Receipt" button, file picker (accepts .jpg, .jpeg, .pdf)
- **ExpenseForm.vue**: Accept optional pre-filled data prop
- No new components needed — reuses existing form

## Agent Design

- Uses `HasStructuredOutput` for guaranteed JSON schema
- Model: Claude Sonnet (fast, cost-effective, excellent vision)
- Default currency: CHF
- Date format: Y-m-d
- Extracts: title, date, description, amount, currency

## Cost

~$0.01-0.05 per receipt scan (Claude Sonnet vision pricing).
