<?php

namespace App\Actions\Pdf;

use Spatie\LaravelPdf\Facades\Pdf;
use Spatie\LaravelPdf\PdfBuilder;

/**
 * Starting point for every PDF the app renders: picks the right Browsershot
 * runtime for the environment so callers only deal with view and data.
 */
class Build
{
    public function execute(string $view, array $data = [], array $margins = [30, 20, 30, 20]): PdfBuilder
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
}
