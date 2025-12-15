<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Expense;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Spatie\LaravelPdf\Facades\Pdf;
use Spatie\LaravelPdf\PdfBuilder;

class PdfController extends Controller
{
 	protected string $filenamePrefix = 'mto-';

 	public function invoice(Invoice $invoice)
 	{
 		$invoice->load(['positions', 'client']);

 		// Generate cached filename with updated_at timestamp
 		$timestamp = $invoice->updated_at->format('d-m-Y-H-i-s');
 		$cachedFilename = "invoices/{$this->filenamePrefix}{$invoice->number}-{$invoice->client->acronym}-{$timestamp}.pdf";
 		$storagePath = "public/media/{$cachedFilename}";

 		// Check if cached PDF exists
 		if (Storage::exists($storagePath)) {
 			return response()->file(
 				Storage::path($storagePath),
 				[
 					'Content-Type' => 'application/pdf',
 					'Cache-Control' => 'no-cache, no-store, must-revalidate',
 					'Pragma' => 'no-cache',
 					'Expires' => '0',
 				]
 			)->setContentDisposition('inline', $this->getInvoiceFilename($invoice));
 		}

 		// Ensure directory exists
 		Storage::makeDirectory('public/media/invoices');

 		// Generate and save PDF
 		$this->buildPdf('pdf.invoice', ['invoice' => $invoice])
 			->headerView('pdf.partials.header')
 			->footerView('pdf.partials.footer')
 			->save(Storage::path($storagePath));

 		return response()->file(
 			Storage::path($storagePath),
 			[
 				'Content-Type' => 'application/pdf',
 				'Cache-Control' => 'no-cache, no-store, must-revalidate',
 				'Pragma' => 'no-cache',
 				'Expires' => '0',
 			]
 		)->setContentDisposition('inline', $this->getInvoiceFilename($invoice));
 	}

 	public function expense(Expense $expense)
 	{
 		// Generate cached filename with updated_at timestamp
 		$timestamp = $expense->updated_at->format('d-m-Y-H-i-s');
 		$cachedFilename = "expenses/{$this->filenamePrefix}{$expense->number}-{$timestamp}.pdf";
 		$storagePath = "public/media/{$cachedFilename}";

 		// Check if cached PDF exists
 		if (Storage::exists($storagePath)) {
 			return response()->file(
 				Storage::path($storagePath),
 				[
 					'Content-Type' => 'application/pdf',
 					'Cache-Control' => 'no-cache, no-store, must-revalidate',
 					'Pragma' => 'no-cache',
 					'Expires' => '0',
 				]
 			)->setContentDisposition('inline', $this->getExpenseFilename($expense));
 		}

 		// Ensure directory exists
 		Storage::makeDirectory('public/media/expenses');

 		// Generate and save PDF
 		$this->buildPdf('pdf.expense', ['expense' => $expense])
 			->headerView('pdf.partials.header')
 			->footerView('pdf.partials.footer')
 			->save(Storage::path($storagePath));

 		return response()->file(
 			Storage::path($storagePath),
 			[
 				'Content-Type' => 'application/pdf',
 				'Cache-Control' => 'no-cache, no-store, must-revalidate',
 				'Pragma' => 'no-cache',
 				'Expires' => '0',
 			]
 		)->setContentDisposition('inline', $this->getExpenseFilename($expense));
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
 			\Carbon\Carbon::parse($expense->date)->format('d.m.Y'),
 			Str::slug($expense->title),
 			$expense->number
 		);
 	}
}
