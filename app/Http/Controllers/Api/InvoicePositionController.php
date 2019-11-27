<?php
namespace App\Http\Controllers\Api;
use App\Models\InvoicePosition;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class InvoicePositionController extends Controller
{
    protected $invoicePosition;
    
    /**
     * Constructor
     * 
     * @param Invoice $invoice
     */

    public function __construct(InvoicePosition $invoicePosition)
    {
        $this->invoicePosition = $invoicePosition;
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  InvoicePosition $invoicePosition
     * @return \Illuminate\Http\Response
     */
    public function destroy(InvoicePosition $invoicePosition)
    {
        $invoicePosition->delete();
        return response()->json('successfully deleted');
    }
}
