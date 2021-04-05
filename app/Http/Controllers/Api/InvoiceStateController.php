<?php
namespace App\Http\Controllers\Api;
use App\Models\InvoiceState;
use App\Models\Invoice;
use App\Http\Resources\InvoiceStateCollection;
use App\Http\Requests\InvoiceUpdateStateRequest;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class InvoiceStateController extends Controller
{
    protected $invoiceState;

    /**
     * Constructor
     * 
     * @param InvoiceState $invoiceState
     */

    public function __construct(InvoiceState $invoiceState)
    {
      $this->invoiceState = $invoiceState;
    }

    /**
     * Get a settings item by key
     * 
     * @return Collection $invoiceStates
     */

    public function index()
    {
      return new InvoiceStateCollection($this->invoiceState->get());
    }

    /**
     * Update the status of the specified resource.
     *
     * @param Invoice $invoice
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function update(Invoice $invoice, InvoiceUpdateStateRequest $request)
    {
      // Set invoice amount to 'zero' if an invoice is cancelled
      if ($request->input('state_id') == 6)
      {
        $invoice->total = 0;
        $invoice->vat = 0;
      }
      $invoice->update($request->all());
      $invoice->save();
      return response()->json('successfully updated');
    }
}
