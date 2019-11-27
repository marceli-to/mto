<?php
namespace App\Http\Controllers\Api;
use App\Models\Invoice;
use App\Models\InvoicePosition;
use App\Http\Resources\InvoiceCollection;
use App\Http\Requests\InvoiceStoreRequest;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    /**
     * Models
     */
    
    protected $invoice;
    protected $invoicePosition;
    
    /**
     * Constructor
     * 
     * @param Invoice $invoice
     */

    public function __construct(Invoice $invoice, InvoicePosition $invoicePosition)
    {
        $this->invoice = $invoice;
        $this->invoicePosition = $invoicePosition;
    }

    /**
     * Get all records
     * 
     * @return \Illuminate\Http\Response
     */

    public function get()
    {
        return new InvoiceCollection($this->invoice->with('client')->orderBy('status')->orderBy('number')->get());
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    
    public function store(InvoiceStoreRequest $request)
    {   
        $invoice = new Invoice($request->all());
        $invoice->save();
        $invoice->positions()->createMany($request->positions);
        $this->createInvoiceNumber($invoice);
        return response()->json(['invoiceId' => $invoice->id]);
    }

    /**
     * Edit a specified resource.
     *
     * @param Invoice $invoice
     * @return \Illuminate\Http\Response
     */
    public function edit(Invoice $invoice)
    {
        return response()->json($this->invoice->with('positions')->find($invoice->id));
    }

    /**
     * Update the status of the specified resource.
     *
     * @param Invoice $invoice
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function update(Invoice $invoice, InvoiceStoreRequest $request)
    {
        $invoice->update($request->all());
        $positions = [];
        $total = 0;
        foreach ($request->positions as $position)
        {
            $position['invoice_id'] = $invoice->id;
            $total += $position['amount'];
            $this->invoicePosition->updateOrCreate(['id' => $position['id']], $position);
        }

        $invoice->total = $total;
        $invoice->save();
        
        return response()->json('successfully updated');
    }

    /**
     * Clone a specified resource.
     *
     * @param  Invoice $invoice
     * @return \Illuminate\Http\Response
     */
    public function clone(Invoice $invoice)
    {
        $clone = $invoice->replicate();
        $clone->title = $invoice->title . ' (copy)';
        $clone->save();
        $this->createInvoiceNumber($clone);
        return response()->json($clone);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  Invoice $invoice
     * @return \Illuminate\Http\Response
     */
    public function destroy(Invoice $invoice)
    {
        $invoice->delete();
        return response()->json('successfully deleted');
    }

    /**
     * Create the invoice number
     * @param  Invoice $invoice
     */
    protected function createInvoiceNumber(Invoice $invoice)
    {
        $invoice->number = date('y', time()) . '.' . str_pad($invoice->id, 4, "0", STR_PAD_LEFT);
        $invoice->save();
    }
}
