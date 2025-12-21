<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Actions\Dashboard\Get as GetAction;

class DashboardController extends Controller
{
    public function get()
    {
        return (new GetAction)->execute();
    }
}
