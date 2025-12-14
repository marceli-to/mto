<?php

namespace App\Actions\Contact;

use App\Models\Contact;
use App\Http\Requests\ContactStoreRequest;

class Update
{
    public function execute(Contact $contact, ContactStoreRequest $request)
    {
        $contact->update($request->all());

        return response()->json('successfully updated');
    }
}
