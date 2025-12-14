<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Expense;
use Illuminate\Support\Str;
use Spatie\LaravelPdf\Facades\Pdf;
use Spatie\LaravelPdf\PdfBuilder;

class PdfController extends Controller
{
    protected string $filenamePrefix = 'mto-';

    public function invoice(Invoice $invoice)
    {
        $invoice->load(['positions', 'client']);

        return $this->buildPdf('pdf.invoice', ['invoice' => $invoice])
            ->headerView('pdf.partials.header')
            ->footerView('pdf.partials.footer')
            ->name($this->getInvoiceFilename($invoice));
    }

    public function expense(Expense $expense)
    {
        return $this->buildPdf('pdf.expense', ['expense' => $expense])
            ->name($this->getExpenseFilename($expense));
    }

    protected function buildPdf(string $view, array $data = [], array $margins = [30, 20, 30, 20]): PdfBuilder
    {
        $pdf = Pdf::view($view, $data)
            ->format('a4')
            ->margins(...$margins);

        if (app()->environment('production')) {
            $pdf->onLambda();
        } else {
            $pdf->withBrowsershot(function (\Spatie\Browsershot\Browsershot $browsershot) {
                $browsershot
                    ->setNodeBinary('/Users/marceli.to/.nvm/versions/node/v22.19.0/bin/node')
                    ->setNpmBinary('/Users/marceli.to/.nvm/versions/node/v22.19.0/bin/npm');
            });
        }

        return $pdf;
    }

    protected function getInvoiceFilename(Invoice $invoice): string
    {
        return sprintf(
            '%s%s-%s-%s.pdf',
            $this->filenamePrefix,
            $invoice->number,
            $invoice->client->acronym,
            Str::slug(str_replace('www.', '', $invoice->title))
        );
    }

    protected function getExpenseFilename(Expense $expense): string
    {
        return sprintf(
            'mto-%s-%s-%s.pdf',
            $expense->date->format('d.m.Y'),
            Str::slug($expense->title),
            $expense->number
        );
    }
}
