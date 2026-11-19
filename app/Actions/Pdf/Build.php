<?php

namespace App\Actions\Pdf;

use Fagus\Laravel\Facades\Fagus;
use Fagus\Laravel\PendingPdf;

/**
 * Starting point for every PDF the app renders. Fagus lays the view out
 * without a browser, in a worker process that stays up between documents.
 *
 * Templates own their page setup in CSS: size and margins in @page, headers
 * and footers via pdf.partials.page-frame. Fonts come from resources/fonts
 * (see config/fagus.php): Poppins for the documents, Liberation Sans for the
 * Swiss QR bill, whose standard only allows Arial, Frutiger, Helvetica or
 * Liberation Sans. Fagus runs no scripts, so nothing in a PDF view may rely on
 * JavaScript.
 */
class Build
{
    public function execute(string $view, array $data = []): PendingPdf
    {
        return Fagus::view($view, $data);
    }
}
