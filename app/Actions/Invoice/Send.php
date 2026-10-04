<?php

namespace App\Actions\Invoice;

use App\Http\Requests\InvoiceSendRequest;
use App\Mail\InvoiceMail;
use App\Models\Invoice;
use App\Models\InvoiceState;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class Send
{
    public function execute(Invoice $invoice, InvoiceSendRequest $request)
    {
        $pdf = new Pdf;

        try {
            $mail = new InvoiceMail(
                $invoice,
                $request->input('subject'),
                $request->input('body'),
                $pdf->execute($invoice),
                $pdf->filename($invoice)
            );

            Mail::to($request->recipients('to'))
                ->cc($request->recipients('cc'))
                ->send($mail);
        } catch (\Throwable $e) {
            Log::error("Failed to send invoice {$invoice->number}: {$e->getMessage()}");

            return response()->json([
                'message' => 'The invoice could not be sent: ' . $e->getMessage(),
            ], 500);
        }

        // An invoice the client has in hand is no longer something we are still
        // putting together, so sending moves it on to pending. Invoices that are
        // already further along keep the state they have.
        if ($invoice->state_id === InvoiceState::DRAFT) {
            $invoice->update(['state_id' => InvoiceState::PENDING]);
        }

        return response()->json([
            'message' => 'Invoice sent',
            'state_id' => $invoice->state_id,
        ]);
    }
}
