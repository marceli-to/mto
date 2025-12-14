<?php

namespace App\Actions\InvoiceState;

use App\Models\InvoiceState;
use App\Http\Resources\InvoiceStateCollection;

class Get
{
    public function execute()
    {
        return new InvoiceStateCollection(InvoiceState::all());
    }
}
