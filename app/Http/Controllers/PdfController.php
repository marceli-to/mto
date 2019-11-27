<?php
namespace App\Http\Controllers;
use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\InvoicePosition;
use PDF;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PdfController extends Controller
{
  // Models
  protected $invoice;
  protected $invoicePosition;

  protected $filenamePrefix = 'mto-';

  public function __construct(Invoice $invoice, InvoicePosition $invoicePosition)
  {
    $this->invoice = $invoice;
    $this->invoicePosition = $invoicePosition;
  }

  /**
   * Generate an invoice
   *
   * @param Invoice $invoice
   * @return \Illuminate\Http\Response
   */
  public function invoice(Invoice $invoice)
  {
    $data = $this->invoice->with('positions')->with('client')->findOrFail($invoice->id);
    $pdf = PDF::loadView('pdf.invoice', compact('data'));
    return $pdf->stream($this->_getFileName($invoice));
  }

  private function _getFileName(Invoice $invoice)
  {
    return $this->filenamePrefix . $invoice->number . '-' . $invoice->client->acronym . '-' . Str::slug(str_replace('www.', '', $invoice->title)) .'.pdf';
  }
}
