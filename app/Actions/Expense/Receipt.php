<?php

namespace App\Actions\Expense;

use App\Models\Expense;
use Illuminate\Support\Facades\Log;

/**
 * The expense's receipt as images ready to embed in the expense PDF, one data
 * URI per page. Photos pass through as they are; a PDF receipt is rasterised
 * page by page, since Chrome can't place a PDF inside the page it prints.
 */
class Receipt
{
    protected const EXTENSIONS = ['jpg', 'jpeg', 'png', 'pdf'];

    public function execute(Expense $expense): array
    {
        $path = $this->path($expense);

        if ($path === null) {
            return [];
        }

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        if ($extension === 'pdf') {
            return $this->rasterise($path);
        }

        $mime = $extension === 'png' ? 'image/png' : 'image/jpeg';

        return ["data:{$mime};base64," . base64_encode(file_get_contents($path))];
    }

    protected function path(Expense $expense): ?string
    {
        foreach (self::EXTENSIONS as $extension) {
            $path = storage_path("app/public/media/expenses/{$expense->number}.{$extension}");
            if (file_exists($path)) {
                return $path;
            }
        }

        return null;
    }

    protected function rasterise(string $path): array
    {
        if (!extension_loaded('imagick')) {
            Log::warning("Cannot render PDF receipt {$path}: the imagick extension is not loaded.");
            return [];
        }

        try {
            $pdf = new \Imagick();
            // Resolution has to be set before reading, or pages come out at 72 dpi
            $pdf->setResolution(150, 150);
            $pdf->readImage($path);

            $pages = [];
            foreach ($pdf as $page) {
                // PDF pages can be transparent, which JPEG turns black
                $page->setImageBackgroundColor('white');
                $page->setImageAlphaChannel(\Imagick::ALPHACHANNEL_REMOVE);
                $page->setImageFormat('jpeg');
                $page->setImageCompressionQuality(85);
                $pages[] = 'data:image/jpeg;base64,' . base64_encode($page->getImageBlob());
            }

            $pdf->clear();

            return $pages;
        } catch (\ImagickException $e) {
            Log::warning("Cannot render PDF receipt {$path}: {$e->getMessage()}");
            return [];
        }
    }
}
