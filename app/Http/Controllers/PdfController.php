<?php
namespace App\Http\Controllers;
use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\InvoicePosition;
use App\Models\Expense;
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

  public function __construct(Invoice $invoice, InvoicePosition $invoicePosition, Expense $expense)
  {
    $this->invoice = $invoice;
    $this->invoicePosition = $invoicePosition;
    $this->expense = $expense;
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

    // Check if a journal is required
    if (count($data->positions) > 4)
    {
      $data['journal'] = $this->_getJournal($data);
    }

    $pdf = PDF::loadView('pdf.invoice', array('data' => $data));
    return $pdf->stream($this->_getFileName($invoice));
  }

  public function invoices()
  {
    $invoices = $this->invoice->with('positions')->with('client')->where('state_id', '>', 1)->where('date', '>', '2019-12-31')->get();
    foreach($invoices as $invoice)
    {
      $filename = 'mto-' . $invoice->number . '-' . $invoice->client->acronym . '-' . \Str::slug(str_replace('www.', '', $invoice->title)) .'.pdf';
      $pdf = PDF::loadView('pdf.invoice', array('data' => $invoice));
      $pdf->save(public_path() . '/storage/media/invoices/' . $filename);
    }
  }

  /**
   * Generate an expense
   *
   * @param Expense $expense
   * @return \Illuminate\Http\Response
   */
  public function expense(Expense $expense)
  {
    $data = $this->expense->findOrFail($expense->id);

    $pdf = PDF::loadView('pdf.expense', array('data' => $data));
    return $pdf->stream($this->_getExpenseFileName($expense));
  }

  private function _getFileName(Invoice $invoice)
  {
    return $this->filenamePrefix . $invoice->number . '-' . $invoice->client->acronym . '-' . Str::slug(str_replace('www.', '', $invoice->title)) .'.pdf';
  }

  private function _getExpenseFileName(Expense $expense)
  {
    return 'm_to-' . Str::slug($expense->title) . '-'. date('d.m.Y', strtotime($expense->date)) . '-' . $expense->number .'.pdf';
  }

  private function _getJournal($invoice)
  {
    $data = [
      'hasJournal' => TRUE,
      'periode' => 'Diverse',
      'description' => 'Gemäss Journal'
    ];

    $totalHours = 0;
    foreach($invoice->positions as $pos)
    {
      $totalHours += $pos->hours;
    }

    $data['totalHours'] = $totalHours;
    return $data;
  }
}
