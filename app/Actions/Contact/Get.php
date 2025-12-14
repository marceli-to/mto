<?php

namespace App\Actions\Contact;

use App\Models\Contact;
use App\Http\Resources\ContactCollection;

class Get
{
    public function execute(?int $clientId = null)
    {
        return new ContactCollection(
            Contact::where('client_id', $clientId)->orderBy('name', 'ASC')->get()
        );
    }
}
