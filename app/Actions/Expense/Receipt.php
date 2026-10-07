<?php

namespace App\Actions\Expense;

use App\Models\Expense;
use Illuminate\Support\Facades\Log;

/**
 * The expense's receipt as images ready to embed in the expense PDF, one data
 * URI per page. A PDF receipt is rasterised page by page, since Chrome can't
 * place a PDF inside the page it prints.
 *
 * Every page is scaled down to print size and recompressed: the HTML goes to
 * Lambda and the PDF comes back from it, and a full-size phone photo pushes
 * the response past Lambda's 6 MB payload limit.
 */
class Receipt
{
    protected const EXTENSIONS = ['jpg', 'jpeg', 'png', 'pdf'];

    // Fits the 160 x 220 mm receipt box at roughly 250 dpi
    protected const MAX_WIDTH = 1600;
    protected const MAX_HEIGHT = 2200;

    protected const QUALITY = 80;

    public function execute(Expense $expense): array
    {
        $path = $this->path($expense);

        if ($path === null) {
            return [];
        }

        $pages = strtolower(pathinfo($path, PATHINFO_EXTENSION)) === 'pdf'
            ? $this->rasterise($path)
            : [$this->orient(file_get_contents($path), $path)];

        return array_map(
            fn (string|\GdImage $page) => 'data:image/jpeg;base64,' . base64_encode($this->shrink($page)),
            $pages
        );
    }

    /**
     * Rescales an image to fit the receipt box and returns it as a JPEG.
     */
    protected function shrink(string|\GdImage $image): string
    {
        $source = is_string($image) ? imagecreatefromstring($image) : $image;

        $width = imagesx($source);
        $height = imagesy($source);
        $scale = min(1, self::MAX_WIDTH / $width, self::MAX_HEIGHT / $height);

        $target = imagecreatetruecolor(max(1, (int) round($width * $scale)), max(1, (int) round($height * $scale)));
        // PNGs can be transparent, which JPEG turns black
        imagefill($target, 0, 0, imagecolorallocate($target, 255, 255, 255));
        imagecopyresampled($target, $source, 0, 0, 0, 0, imagesx($target), imagesy($target), $width, $height);

        ob_start();
        imagejpeg($target, null, self::QUALITY);

        return ob_get_clean();
    }

    /**
     * Applies a photo's EXIF rotation, which is lost once GD re-encodes it.
     */
    protected function orient(string $data, string $path): string|\GdImage
    {
        $orientation = function_exists('exif_read_data')
            ? (@exif_read_data($path)['Orientation'] ?? 1)
            : 1;

        $angle = [3 => 180, 6 => -90, 8 => 90][$orientation] ?? 0;

        if ($angle === 0) {
            return $data;
        }

        return imagerotate(imagecreatefromstring($data), $angle, 0);
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
                $page->setImageCompressionQuality(95);
                $pages[] = $page->getImageBlob();
            }

            $pdf->clear();

            return $pages;
        } catch (\ImagickException $e) {
            Log::warning("Cannot render PDF receipt {$path}: {$e->getMessage()}");
            return [];
        }
    }
}
