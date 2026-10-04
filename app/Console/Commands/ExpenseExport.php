<?php

namespace App\Console\Commands;

use App\Actions\Pdf\Build;
use App\Models\Expense;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Options;
use OpenSpout\Writer\XLSX\Writer;
use Symfony\Component\Process\ExecutableFinder;

/**
 * Exports all expenses of a year for the accountant: a PDF (overview plus
 * one page per expense with its receipt) and an Excel spreadsheet.
 *
 * The PDF is rendered via App\Actions\Pdf\Build (Lambda in production,
 * local Browsershot elsewhere). Lambda caps request/response payloads at 6 MB,
 * so expense pages are rendered in batches of limited receipt size. Receipts
 * that are PDFs themselves are appended after their expense page with
 * pdfunite/ghostscript.
 */
class ExpenseExport extends Command
{
    protected $signature = 'expense:export
        {year? : Year to export (defaults to last year)}
        {--no-pdf : Only create the Excel file}';

    protected $description = 'Export all expenses of a year as PDF (incl. receipts) and Excel for the accountant';

    /** Receipt images are downscaled to this many pixels on the longer side to keep the PDF small. */
    protected const RECEIPT_MAX_PX = 1800;

    /** Max. receipt image bytes per render call, keeps HTML and PDF well below Lambda's 6 MB payload limit. */
    protected const BATCH_MAX_BYTES = 2_500_000;

    public function handle(): int
    {
        $year = (int) ($this->argument('year') ?? now()->subYear()->year);

        $expenses = Expense::whereYear('date', $year)
            ->orderBy('date')
            ->orderBy('number')
            ->get();

        if ($expenses->isEmpty()) {
            $this->info("No expenses found for {$year}");
            return self::SUCCESS;
        }

        $directory = storage_path('app/public/media/downloads');
        File::ensureDirectoryExists($directory);
        $basePath = "{$directory}/mto-ausgaben-{$year}";

        $totals = $this->totals($expenses);

        $this->table(
            ['Currency', 'Count', 'Total'],
            collect($totals)->map(fn ($t, $currency) => [$currency, $t['count'], number_format($t['amount'], 2, '.', "'")])->values()->all()
        );

        $missing = $expenses->filter(fn ($expense) => !$this->receiptPath($expense));
        if ($missing->isNotEmpty()) {
            $this->warn('No receipt found for: ' . $missing->pluck('number')->join(', '));
        }

        $this->exportExcel($expenses, $totals, "{$basePath}.xlsx", $year);
        $this->info("Excel: {$basePath}.xlsx");

        if (!$this->option('no-pdf')) {
            $this->exportPdf($expenses, $totals, "{$basePath}.pdf", $year);
            $this->info("PDF:   {$basePath}.pdf");
        }

        return self::SUCCESS;
    }

    protected function totals(Collection $expenses): array
    {
        return $expenses
            ->groupBy(fn ($expense) => $expense->currency ?? 'CHF')
            ->map(fn ($group) => ['count' => $group->count(), 'amount' => $group->sum('amount')])
            ->sortKeys()
            ->all();
    }

    protected function receiptPath(Expense $expense): ?string
    {
        foreach (['jpg', 'jpeg', 'png', 'pdf'] as $extension) {
            $path = storage_path("app/public/media/expenses/{$expense->number}.{$extension}");
            if (file_exists($path)) {
                return $path;
            }
        }

        return null;
    }

    protected function exportExcel(Collection $expenses, array $totals, string $path, int $year): void
    {
        $bold = new Style(fontBold: true);
        $date = new Style(format: 'dd.mm.yyyy');
        $amount = new Style(format: '#,##0.00');
        $boldAmount = new Style(fontBold: true, format: '#,##0.00');

        $options = new Options();
        $options->setColumnWidth(12, 1);
        $options->setColumnWidth(10, 2);
        $options->setColumnWidth(40, 3);
        $options->setColumnWidth(50, 4);
        $options->setColumnWidth(10, 5);
        $options->setColumnWidth(12, 6);
        $options->setColumnWidth(16, 7);

        $writer = new Writer($options);
        $writer->openToFile($path);
        $writer->getCurrentSheet()->setName("Ausgaben {$year}");

        $writer->addRow(Row::fromValuesWithStyle(
            ['Datum', 'Nummer', 'Titel', 'Beschreibung', 'Währung', 'Betrag', 'Beleg'],
            $bold
        ));

        foreach ($expenses as $expense) {
            $receipt = $this->receiptPath($expense);

            $writer->addRow(new Row([
                Cell::fromValue(new \DateTimeImmutable($expense->date), $date),
                Cell::fromValue($expense->number),
                Cell::fromValue($expense->title),
                Cell::fromValue($expense->description ?? ''),
                Cell::fromValue($expense->currency ?? 'CHF'),
                Cell::fromValue((float) $expense->amount, $amount),
                Cell::fromValue($receipt ? basename($receipt) : ''),
            ]));
        }

        // Totals per currency as live formulas, so edits in the sheet stay consistent
        $lastRow = $expenses->count() + 1;
        $writer->addRow(Row::fromValues([]));

        foreach (array_keys($totals) as $currency) {
            $writer->addRow(new Row([
                Cell::fromValue(''),
                Cell::fromValue(''),
                Cell::fromValue("Total {$currency}", $bold),
                Cell::fromValue(''),
                Cell::fromValue($currency, $bold),
                Cell::fromValue("=SUMIF(E2:E{$lastRow},\"{$currency}\",F2:F{$lastRow})", $boldAmount),
            ]));
        }

        $writer->close();
    }

    protected function exportPdf(Collection $expenses, array $totals, string $path, int $year): void
    {
        $merger = $this->pdfMerger();
        $workDir = storage_path('app/tmp/expense-export-' . Str::random(8));
        File::ensureDirectoryExists($workDir);

        try {
            $parts = [];
            $parts[] = $this->renderPdf($workDir, count($parts), [
                'overview' => true,
                'expenses' => $expenses,
                'totals' => $totals,
                'year' => $year,
            ]);

            // Expense pages are rendered in batches; a batch ends when it gets too
            // large for Lambda or when an expense has a PDF receipt, which is then
            // appended right after it.
            $batch = [];
            $batchBytes = 0;
            $bar = $this->output->createProgressBar($expenses->count());

            foreach ($expenses as $expense) {
                $receipt = $this->receiptPath($expense);
                $isPdf = $receipt && strtolower(pathinfo($receipt, PATHINFO_EXTENSION)) === 'pdf';

                $image = $receipt && !$isPdf ? $this->imageDataUri($receipt) : null;

                if ($batch && $batchBytes + strlen($image ?? '') > self::BATCH_MAX_BYTES) {
                    $parts[] = $this->renderPdf($workDir, count($parts), ['pages' => $batch, 'year' => $year]);
                    $batch = [];
                    $batchBytes = 0;
                }

                $batch[] = ['expense' => $expense, 'image' => $image, 'pdf' => $isPdf];
                $batchBytes += strlen($image ?? '');
                $bar->advance();

                if ($isPdf) {
                    $parts[] = $this->renderPdf($workDir, count($parts), ['pages' => $batch, 'year' => $year]);
                    $parts[] = $receipt;
                    $batch = [];
                    $batchBytes = 0;
                }
            }

            if ($batch) {
                $parts[] = $this->renderPdf($workDir, count($parts), ['pages' => $batch, 'year' => $year]);
            }

            $bar->finish();
            $this->newLine();

            $this->mergePdfs($merger, $parts, $path);
        } finally {
            File::deleteDirectory($workDir);
        }
    }

    protected function renderPdf(string $workDir, int $index, array $data): string
    {
        $path = "{$workDir}/part-{$index}.pdf";

        (new Build)->execute('pdf.expense-export', $data)
            ->headerView('pdf.partials.header')
            ->footerView('pdf.partials.footer')
            ->save($path);

        return $path;
    }

    /**
     * Downscale and auto-rotate receipt photos (phone pictures are often
     * several MB and rely on EXIF orientation). Uses Imagick, falls back to GD.
     */
    protected function imageDataUri(string $path): string
    {
        $mime = mime_content_type($path) ?: 'image/jpeg';

        if (class_exists(\Imagick::class)) {
            $image = new \Imagick($path);
            $image->autoOrient();

            if (max($image->getImageWidth(), $image->getImageHeight()) > self::RECEIPT_MAX_PX) {
                $image->thumbnailImage(self::RECEIPT_MAX_PX, self::RECEIPT_MAX_PX, true);
            }

            if ($image->getImageFormat() === 'JPEG') {
                $image->setImageCompressionQuality(80);
            }

            $image->stripImage();
            $blob = $image->getImageBlob();
            $image->clear();

            return "data:{$mime};base64," . base64_encode($blob);
        }

        if (extension_loaded('gd') && $image = @imagecreatefromstring(file_get_contents($path))) {
            return "data:{$mime};base64," . base64_encode($this->downscaleWithGd($image, $path, $mime));
        }

        return "data:{$mime};base64," . base64_encode(file_get_contents($path));
    }

    protected function downscaleWithGd(\GdImage $image, string $path, string $mime): string
    {
        $orientation = $mime === 'image/jpeg' && function_exists('exif_read_data')
            ? (@exif_read_data($path)['Orientation'] ?? 1)
            : 1;

        $image = match ($orientation) {
            3 => imagerotate($image, 180, 0),
            6 => imagerotate($image, -90, 0),
            8 => imagerotate($image, 90, 0),
            default => $image,
        };

        $width = imagesx($image);
        $height = imagesy($image);
        $scale = self::RECEIPT_MAX_PX / max($width, $height);

        if ($scale < 1) {
            $image = imagescale($image, (int) round($width * $scale), (int) round($height * $scale));
        }

        ob_start();
        $mime === 'image/png' ? imagepng($image) : imagejpeg($image, null, 80);

        return ob_get_clean();
    }

    /**
     * @return array{0: string, 1: string} [tool, binary]
     */
    protected function pdfMerger(): array
    {
        $finder = new ExecutableFinder();

        if ($binary = $finder->find('pdfunite')) {
            return ['pdfunite', $binary];
        }

        if ($binary = $finder->find('gs')) {
            return ['gs', $binary];
        }

        throw new \RuntimeException('Neither pdfunite (poppler) nor gs (ghostscript) is installed — needed to merge the PDF. Install with `brew install poppler`.');
    }

    protected function mergePdfs(array $merger, array $parts, string $output): void
    {
        [$tool, $binary] = $merger;

        $command = $tool === 'pdfunite'
            ? [$binary, ...$parts, $output]
            : [$binary, '-q', '-dNOPAUSE', '-dBATCH', '-sDEVICE=pdfwrite', '-dPassThroughJPEGImages=true', "-sOutputFile={$output}", ...$parts];

        Process::timeout(600)->run($command)->throw();
    }
}
