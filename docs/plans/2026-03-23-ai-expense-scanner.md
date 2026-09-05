# AI-Powered Expense Scanner — Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Upload a JPG or PDF receipt and have Claude extract expense fields automatically, pre-filling the existing expense form for review before saving.

**Architecture:** New `POST /api/expense/scan` endpoint accepts a file upload, sends it to Claude via the Laravel AI SDK's `ReceiptScanner` agent with structured output, and returns extracted fields (title, date, description, amount, currency) as JSON. The frontend adds a "Scan Receipt" button that triggers a file picker, calls the scan endpoint, and pre-fills the existing `ExpenseForm` with the response.

**Tech Stack:** Laravel AI SDK (`laravel/ai`), Anthropic Claude Sonnet (vision), Vue 3, FilePond

---

### Task 1: Install and configure Laravel AI SDK

**Files:**
- Modify: `composer.json` (via composer)
- Create: `config/ai.php` (via artisan)
- Modify: `.env`

**Step 1: Install the package**

Run: `composer require laravel/ai`

**Step 2: Publish config and run migrations**

Run: `php artisan vendor:publish --provider="Laravel\Ai\AiServiceProvider" && php artisan migrate`

**Step 3: Add Anthropic API key to `.env`**

Add this line to `.env`:

```
ANTHROPIC_API_KEY=your-key-here
```

**Step 4: Verify config**

Run: `php artisan config:clear && php artisan tinker --execute="echo config('ai.providers.anthropic.driver');"`
Expected: `anthropic`

**Step 5: Commit**

```bash
git add composer.json composer.lock config/ai.php database/migrations/*agent*
git commit -m "feat: install laravel/ai SDK with Anthropic provider"
```

---

### Task 2: Create the ReceiptScanner agent

**Files:**
- Create: `app/Ai/Agents/ReceiptScanner.php`

**Step 1: Create the agent class**

```php
<?php

namespace App\Ai\Agents;

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
- date: The date on the receipt in Y-m-d format (e.g. "2026-03-23"). If unclear, use today's date.
- description: A brief summary of the items/services on the receipt. Include vendor name, individual items if visible, and any reference numbers.
- amount: The total amount as a decimal number (e.g. 42.50). Use the final total, not subtotals.
- currency: The 3-letter currency code (CHF, EUR, or USD). Default to CHF if unclear.

Only return the structured data. Do not add commentary.
PROMPT;
    }

    public function schema(\Laravel\Ai\Support\JsonSchema $schema): array
    {
        return [
            'title' => $schema->string()->required(),
            'date' => $schema->string()->required(),
            'description' => $schema->string()->required(),
            'amount' => $schema->number()->required(),
            'currency' => $schema->string()->required(),
        ];
    }
}
```

**Step 2: Verify the class is autoloadable**

Run: `php artisan tinker --execute="new App\Ai\Agents\ReceiptScanner();"`
Expected: No errors

**Step 3: Commit**

```bash
git add app/Ai/Agents/ReceiptScanner.php
git commit -m "feat: add ReceiptScanner AI agent with structured output"
```

---

### Task 3: Add the scan endpoint

**Files:**
- Modify: `app/Http/Controllers/Api/ExpenseController.php`
- Modify: `routes/api.php`

**Step 1: Add the `scan` method to ExpenseController**

Add this use statement at the top of `app/Http/Controllers/Api/ExpenseController.php`:

```php
use App\Ai\Agents\ReceiptScanner;
use Laravel\Ai\Enums\Lab;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
```

Add this method to the class:

```php
public function scan(Request $request)
{
    $request->validate([
        'receipt' => 'required|file|mimes:jpg,jpeg,pdf|max:10240'
    ]);

    $file = $request->file('receipt');
    $extension = $file->getClientOriginalExtension();

    // Store temp file
    $tempFilename = Str::uuid() . '.' . $extension;
    $file->storeAs('public/temp', $tempFilename);
    $tempPath = storage_path('app/public/temp/' . $tempFilename);

    try {
        // Build the prompt with the file attachment
        $agent = new ReceiptScanner();

        $response = $agent->prompt(
            'Extract the expense data from this receipt.',
            provider: Lab::Anthropic,
            model: 'claude-sonnet-4-20250514',
            files: [$tempPath],
        );

        // Move file to temp for later use by the expense store flow
        // Rename to .jpg for consistency with the existing upload flow
        $finalTempName = Str::uuid() . '.jpg';
        if ($extension === 'pdf') {
            // Keep PDF in temp as-is for now, the store action handles .jpg
            // We'll keep the original temp file for the receipt
            $finalTempName = $tempFilename;
        } else {
            Storage::move('public/temp/' . $tempFilename, 'public/temp/' . $finalTempName);
        }

        return response()->json([
            'title' => $response['title'] ?? '',
            'date' => $response['date'] ?? now()->format('Y-m-d'),
            'description' => $response['description'] ?? '',
            'amount' => $response['amount'] ?? 0,
            'currency' => $response['currency'] ?? 'CHF',
            'temp_file' => $finalTempName,
        ]);
    } catch (\Exception $e) {
        // Clean up temp file on error
        Storage::delete('public/temp/' . $tempFilename);

        return response()->json([
            'message' => 'Failed to scan receipt: ' . $e->getMessage()
        ], 422);
    }
}
```

**Step 2: Add the route**

In `routes/api.php`, add inside the `auth:sanctum` middleware group, in the Expense routes section:

```php
Route::post('expense/scan', [ExpenseController::class, 'scan']);
```

**Step 3: Commit**

```bash
git add app/Http/Controllers/Api/ExpenseController.php routes/api.php
git commit -m "feat: add POST /api/expense/scan endpoint for AI receipt scanning"
```

---

### Task 4: Update the frontend — add "Scan Receipt" button to ExpenseList

**Files:**
- Modify: `resources/js/spa/components/expenses/ExpenseList.vue`

**Step 1: Add scan state and logic**

Add the import for the scan icon at line 3 (alongside existing Phosphor imports):

```js
import { PhPlus, PhPencil, PhTrash, PhFilePdf, PhCurrencyDollar, PhScan } from '@phosphor-icons/vue'
```

Add `post` to the useApi destructure (line 12):

```js
const { get, del, post } = useApi()
```

Add these refs after the existing refs (~line 20):

```js
const scanning = ref(false)
const scanFileInput = ref(null)
```

Add these functions after the `openCreate` function:

```js
function triggerScan() {
  scanFileInput.value.click()
}

async function handleScanFile(event) {
  const file = event.target.files[0]
  if (!file) return

  scanning.value = true
  try {
    const formData = new FormData()
    formData.append('receipt', file)

    const data = await post('/api/expense/scan', formData)
    flyout.value = { show: true, expenseId: null, prefill: data }
    success('Receipt scanned successfully')
  } catch (e) {
    error('Failed to scan receipt')
  } finally {
    scanning.value = false
    event.target.value = ''
  }
}
```

Update `closeFlyout` to clear prefill:

```js
function closeFlyout() {
  flyout.value = { show: false, expenseId: null, prefill: null }
}
```

**Step 2: Add the scan button and hidden file input to the template**

In the template, add the scan button next to the existing "Add Expense" button (after the `<button @click="openCreate" ...>` block):

```html
<button
  @click="triggerScan"
  :disabled="scanning"
  class="p-1.5 text-gray-400 hover:text-gray-600 hover:bg-gray-100 cursor-pointer rounded-sm transition-colors disabled:opacity-50"
  title="Scan Receipt"
>
  <PhScan class="w-4 h-4" :class="{ 'animate-pulse': scanning }" />
</button>
```

Add the hidden file input right before the closing `</div>` of the page header:

```html
<input
  ref="scanFileInput"
  type="file"
  accept=".jpg,.jpeg,.pdf"
  class="hidden"
  @change="handleScanFile"
/>
```

**Step 3: Pass prefill data to ExpenseForm**

Update the `<ExpenseForm>` inside the `<Flyout>`:

```html
<ExpenseForm
  :expense-id="flyout.expenseId"
  :prefill="flyout.prefill"
  @saved="onExpenseSaved"
  @cancel="closeFlyout"
/>
```

**Step 4: Add scanning overlay**

Add this right after the page header div, before the loading state div:

```html
<!-- Scanning Overlay -->
<div v-if="scanning" class="text-center py-16">
  <div class="animate-pulse text-gray-500">Scanning receipt...</div>
</div>
```

**Step 5: Commit**

```bash
git add resources/js/spa/components/expenses/ExpenseList.vue
git commit -m "feat: add scan receipt button to expense list"
```

---

### Task 5: Update ExpenseForm to accept pre-filled data

**Files:**
- Modify: `resources/js/spa/components/expenses/ExpenseForm.vue`

**Step 1: Add prefill prop**

Add to the `defineProps` object:

```js
prefill: {
  type: Object,
  default: null
}
```

**Step 2: Use prefill data on mount**

Update the `fetchExpense` function to handle prefill. Replace the `if (!isEdit.value)` block:

```js
async function fetchExpense() {
  if (!isEdit.value) {
    if (props.prefill) {
      expense.value = {
        title: props.prefill.title || '',
        description: props.prefill.description || '',
        date: props.prefill.date || new Date().toISOString().split('T')[0],
        amount: props.prefill.amount || '',
        currency: props.prefill.currency || 'CHF',
        temp_file: props.prefill.temp_file || '',
        delete_file: false
      }
    } else {
      expense.value.date = new Date().toISOString().split('T')[0]
    }
    return
  }
  // ... rest of edit loading stays the same
```

**Step 3: Watch for prefill changes**

Add a watcher for the prefill prop:

```js
watch(() => props.prefill, (newPrefill) => {
  if (newPrefill && !isEdit.value) {
    expense.value = {
      title: newPrefill.title || '',
      description: newPrefill.description || '',
      date: newPrefill.date || new Date().toISOString().split('T')[0],
      amount: newPrefill.amount || '',
      currency: newPrefill.currency || 'CHF',
      temp_file: newPrefill.temp_file || '',
      delete_file: false
    }
  }
}, { deep: true })
```

**Step 4: Commit**

```bash
git add resources/js/spa/components/expenses/ExpenseForm.vue
git commit -m "feat: accept prefill data in ExpenseForm from receipt scan"
```

---

### Task 6: Update upload handling for PDF support

The existing `UploadController` only accepts JPG. The scan endpoint handles its own upload, but we should also update the `Store` action to handle non-JPG temp files from the scan flow.

**Files:**
- Modify: `app/Actions/Expense/Store.php`

**Step 1: Update moveUploadedFile to handle both JPG and PDF**

Replace the `moveUploadedFile` method:

```php
protected function moveUploadedFile(Expense $expense, ?string $tempFile): void
{
    if (!$tempFile) {
        return;
    }

    $tempPath = 'public/temp/' . $tempFile;
    $extension = pathinfo($tempFile, PATHINFO_EXTENSION) ?: 'jpg';
    $finalPath = 'public/media/expenses/' . $expense->number . '.' . $extension;

    if (Storage::exists($tempPath)) {
        Storage::move($tempPath, $finalPath);
    }
}
```

**Step 2: Commit**

```bash
git add app/Actions/Expense/Store.php
git commit -m "feat: support PDF receipts in expense file storage"
```

---

### Task 7: Manual end-to-end test

**Step 1: Build frontend**

Run: `npm run build`

**Step 2: Test the scan flow**

1. Open the app and navigate to Expenses
2. Click the scan icon (next to the + button)
3. Select a JPG or PDF receipt
4. Wait for "Receipt scanned successfully" toast
5. Verify the flyout opens with pre-filled fields
6. Review and adjust fields as needed
7. Click "Create" to save the expense
8. Verify the expense appears in the list

**Step 3: Test edge cases**

- Upload a blurry receipt — verify fields still get populated (may need manual correction)
- Upload a PDF receipt — verify it works
- Cancel the scan mid-flight — verify no orphaned temp files

**Step 4: Final commit**

```bash
git add -A
git commit -m "feat: AI-powered expense creation from receipt scan"
```
