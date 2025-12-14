<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Actions\Rate\Get as GetAction;

class RateController extends Controller
{
    public function get()
    {
        return (new GetAction)->execute();
    }
}
