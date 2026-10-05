<?php

namespace App\Actions\Payment;

use App\Ai\Agents\StatementScanner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Files\Document;

class Scan
{
    /**
     * Read the credits off an uploaded bank statement and match each one to
     * an invoice. Nothing is changed here — the result is a proposal the user
     * reviews before Apply marks anything paid.
     */
    public function execute(Request $request)
    {
        $request->validate([
            'temp_file' => 'required|string',
        ]);

        $tempFilename = basename($request->input('temp_file'));

        if (!Storage::exists('public/temp/' . $tempFilename)) {
            return response()->json(['message' => 'File not found'], 404);
        }

        if (strtolower(pathinfo($tempFilename, PATHINFO_EXTENSION)) !== 'pdf') {
            return response()->json(['message' => 'Please upload the statement as PDF'], 422);
        }

        try {
            $response = (new StatementScanner)->prompt(
                'List the incoming payments on this bank statement.',
                attachments: [Document::fromPath(storage_path('app/public/temp/' . $tempFilename))],
                provider: Lab::Anthropic,
                model: config('services.anthropic.receipt_model'),
            );
        } catch (\Exception $e) {
            Log::error('Statement scan failed', [
                'file' => $tempFilename,
                'model' => config('services.anthropic.receipt_model'),
                'exception' => $e,
            ]);

            return response()->json([
                'message' => 'Failed to scan statement: ' . $e->getMessage()
            ], 422);
        } finally {
            Storage::delete('public/temp/' . $tempFilename);
        }

        return response()->json([
            'payments' => (new Matcher)->execute($response['payments'] ?? []),
        ]);
    }
}
