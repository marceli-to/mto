<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\LaravelPdf\Facades\Pdf;

class GeneratePdf extends Command
{
    protected $signature = 'app:generate-pdf {--local : Generate locally instead of Lambda}';

    protected $description = 'Generate a test PDF using AWS Lambda';

    public function handle(): int
    {
        $outputPath = storage_path('app/invoice.pdf');

        $this->info('Generating PDF...');

        $pdf = Pdf::view('pdf.invoice', [
            'invoiceNumber' => 'INV-'.now()->format('Ymd').'-001',
            'customerName' => 'John Doe',
        ])
            ->format('a4')
            ->headerView('pdf.partials.header')
            ->footerView('pdf.partials.footer')
            ->margins(30, 20, 30, 20);

        if (! $this->option('local')) {
            $this->info('Using AWS Lambda...');
            $pdf->onLambda();
        } else {
            $this->info('Using local Browsershot...');
        }

        $pdf->save($outputPath);

        $this->info("PDF saved to: {$outputPath}");

        return self::SUCCESS;
    }
}
