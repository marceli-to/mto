<?php

namespace App\Actions\Contact;

use App\Models\Contact;

class Show
{
    public function execute(Contact $contact)
    {
        return response()->json($contact);
    }
}
