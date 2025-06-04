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
use Sprain\SwissQrBill as QrBill;

class PdfController extends Controller
{
  // Models
  protected $invoice;
  protected $invoicePosition;

  // Prefix for generated files
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
    // Get invoice data
    $data = $this->invoice->with('positions')->with('client')->findOrFail($invoice->id);

    // Get QR Code
    $qr = $this->getQrImage($data);

    // Check if a journal is required
    if (count($data->positions) > 6)
    {
      $data['journal'] = $this->_getJournal($data);
    }

    $pdf = PDF::loadView('pdf.invoice', array('data' => $data, 'qr' => $qr));
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

  public function expenses()
  {
    $expenses = $this->expense->whereYear('date', '=', 2024)->get();
    foreach($expenses as $expense)
    {
      $filename = $this->_getExpenseFileName($expense);
      $pdf = PDF::loadView('pdf.expense', array('data' => $expense));
      $pdf->save(public_path() . '/storage/media/expenses/export/' . $filename);
    }
  }

  private function _getFileName(Invoice $invoice)
  {
    return $this->filenamePrefix . $invoice->number . '-' . $invoice->client->acronym . '-' . Str::slug(str_replace('www.', '', $invoice->title)) .'.pdf';
  }

  private function _getExpenseFileName(Expense $expense)
  {
    return 'm_to-' . date('d.m.Y', strtotime($expense->date)) . '-' . Str::slug($expense->title) . '-'. $expense->number .'.pdf';
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

  private function getQrImage(Invoice $invoice)
  {
    // Create a new instance of QrBill, containing default headers with fixed values
    $qrBill = QrBill\QrBill::create();

    // Add creditor information
    // Who will receive the payment and to which bank account?
    $qrBill->setCreditor(
      QrBill\DataGroup\Element\CombinedAddress::create(
        config('invoice.beneficiary_name'),
        config('invoice.beneficiary_street'),
        config('invoice.beneficiary_city'),
        config('invoice.beneficiary_country'),
      )
    );

    $qrBill->setCreditorInformation(
      QrBill\DataGroup\Element\CreditorInformation::create(
        str_replace(' ', '', config('invoice.classic_iban'))
      )
    );

    // Add payment amount information
    // The currency must be defined.
    $qrBill->setPaymentAmountInformation(
      QrBill\DataGroup\Element\PaymentAmountInformation::create(
        config('invoice.currency'),
        $invoice->grandtotal
      )
    );

    // Add payment reference
    // Explicitly define that no reference number will be used by setting TYPE_NON.
    $qrBill->setPaymentReference(
      QrBill\DataGroup\Element\PaymentReference::create(
        QrBill\DataGroup\Element\PaymentReference::TYPE_NON
      )
    );
    
    // Return Data URI
    return $qrBill->getQrCode()->writeDataUri();

  }
}
