<?php

namespace App\Actions\Invoice;

use App\Actions\Pdf\Build as BuildPdf;
use App\Actions\Invoice\QrBill;
use App\Models\Invoice;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Renders an invoice to a PDF on disk and hands back the path. The cached file
 * carries the invoice's updated_at, so an edited invoice is rendered anew while
 * an untouched one is served straight from storage.
 */
class Pdf
{
    protected string $filenamePrefix = 'mto-';

    public function execute(Invoice $invoice): string
    {
        $invoice->load(['positions', 'client']);

        $timestamp = $invoice->updated_at->format('d-m-Y-H-i-s');
        $storagePath = "public/media/invoices/{$this->filenamePrefix}{$invoice->number}-{$invoice->client->acronym}-{$timestamp}.pdf";

        if (Storage::exists($storagePath)) {
            return Storage::path($storagePath);
        }

        $dir = Storage::path('public/media/invoices');
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        // Rendered full-bleed with no Chrome header/footer: the template places
        // its own repeating header and footer, which lets the QR bill claim the
        // whole of the final sheet.
        (new BuildPdf)->execute('pdf.invoice', [
            'invoice' => $invoice,
            'qrBill' => (new QrBill)->execute($invoice),
        ], [0, 0, 0, 0])->save(Storage::path($storagePath));

        return Storage::path($storagePath);
    }

    /**
     * The name the recipient sees — on download as well as in a mail attachment.
     */
    public function filename(Invoice $invoice): string
    {
        return sprintf(
            '%s%s-%s-%s.pdf',
            $this->filenamePrefix,
            $invoice->number,
            $invoice->client->acronym,
            Str::slug(str_replace('www.', '', $invoice->title))
        );
    }
}
