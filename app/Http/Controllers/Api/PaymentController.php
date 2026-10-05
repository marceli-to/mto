<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Actions\Payment\Scan as ScanAction;
use App\Actions\Payment\Apply as ApplyAction;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function scan(Request $request)
    {
        return (new ScanAction)->execute($request);
    }

    public function apply(Request $request)
    {
        return (new ApplyAction)->execute($request);
    }
}
