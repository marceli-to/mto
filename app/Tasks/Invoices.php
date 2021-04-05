<?php
namespace App\Tasks;
use App\Models\Invoice;
use PDF;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class Invoices
{
  public function __invoke()
  {
    $invoices = \App\Models\Invoice::with('positions', 'client')->where('processed', '=', 0)->limit(5)->get();
    foreach($invoices as $invoice)
    {
      $filename = 'mto-' . $invoice->number . '-' . $invoice->client->acronym . '-' . \Str::slug(str_replace('www.', '', $invoice->title)) .'.pdf';
      $pdf = PDF::loadView('pdf.invoice', array('data' => $invoice));
      $pdf->save(public_path() . '/storage/media/invoices/' . $filename);
      $invoice->processed = 1;
      $invoice->save();
    }
    return TRUE;
  }
}