<?php

namespace App\Http\Controllers\Api;

use App\Models\Expense;
use App\Http\Controllers\Controller;
use App\Http\Requests\ExpenseStoreRequest;
use App\Actions\Expense\Get as GetAction;
use App\Actions\Expense\Show as ShowAction;
use App\Actions\Expense\Store as StoreAction;
use App\Actions\Expense\Update as UpdateAction;
use App\Actions\Expense\Delete as DeleteAction;
use App\Ai\Agents\ReceiptScanner;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Files\Image;
use Laravel\Ai\Files\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ExpenseController extends Controller
{
    public function get()
    {
        return (new GetAction)->execute();
    }

    public function store(ExpenseStoreRequest $request)
    {
        return (new StoreAction)->execute($request);
    }

    public function edit(Expense $expense)
    {
        return (new ShowAction)->execute($expense);
    }

    public function update(Expense $expense, ExpenseStoreRequest $request)
    {
        return (new UpdateAction)->execute($expense, $request);
    }

    public function destroy(Expense $expense)
    {
        return (new DeleteAction)->execute($expense);
    }

    public function scan(Request $request)
    {
        $request->validate([
            'temp_file' => 'required|string'
        ]);

        $tempFilename = $request->input('temp_file');
        $tempPath = storage_path('app/public/temp/' . $tempFilename);

        if (!Storage::exists('public/temp/' . $tempFilename)) {
            return response()->json(['message' => 'File not found'], 404);
        }

        $extension = strtolower(pathinfo($tempFilename, PATHINFO_EXTENSION));

        try {
            $attachment = $extension === 'pdf'
                ? Document::fromPath($tempPath)
                : Image::fromPath($tempPath);

            $response = (new ReceiptScanner)->prompt(
                'Extract the expense data from this receipt.',
                attachments: [$attachment],
                provider: Lab::Anthropic,
                model: 'claude-sonnet-4-20250514',
            );

            return response()->json([
                'title' => $response['title'] ?? '',
                'date' => $response['date'] ?? now()->format('Y-m-d'),
                'description' => $response['description'] ?? '',
                'amount' => $response['amount'] ?? 0,
                'currency' => $response['currency'] ?? 'CHF',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to scan receipt: ' . $e->getMessage()
            ], 422);
        }
    }
}
