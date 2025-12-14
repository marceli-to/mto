<?php

namespace App\Actions\Contact;

use App\Models\Contact;

class Delete
{
    public function execute(Contact $contact)
    {
        $contact->delete();

        return response()->json('successfully deleted');
    }
}
