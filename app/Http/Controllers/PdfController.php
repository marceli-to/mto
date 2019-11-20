<?php
namespace App\Http\Controllers;
use App\Http\Controllers\Controller;
use PDF;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;

class PdfController extends Controller
{
  protected $filenamePrefix = 'mto-';

  public function __construct()
  {

  }

  public function invoice()
  {
    $data = [];
    $pdf = PDF::loadView('pdf.invoice', $data);
    return $pdf->stream($this->_getFileName('rechnung'));
  }

  private function _getFileName($type = NULL)
  {
    return $this->filenamePrefix . $type . '-' . date('d.m.Y', time()) . '.pdf';
  }
}
