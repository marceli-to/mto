<?php
namespace App\Http\Controllers\Api;
use App\Models\Rate;
use App\Http\Resources\RateCollection;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class RateController extends Controller
{
    protected $rate;

    /**
     * Constructor
     * 
     * @param Rate $rate
     */

    public function __construct(Rate $rate)
    {
        $this->rate = $rate;
    }

    /**
     * Get a settings item by key
     * 
     * @return Collection $ates
     */

    public function get()
    {
        return new RateCollection($this->rate->get());
    }
}
