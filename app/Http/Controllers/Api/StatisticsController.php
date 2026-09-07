<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Actions\Statistics\Get as GetAction;

class StatisticsController extends Controller
{
    public function get()
    {
        return (new GetAction)->execute();
    }
}
