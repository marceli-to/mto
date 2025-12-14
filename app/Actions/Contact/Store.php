<?php

namespace App\Actions\Contact;

use App\Models\Contact;
use App\Http\Requests\ContactStoreRequest;

class Store
{
    public function execute(ContactStoreRequest $request)
    {
        $contact = new Contact($request->all());
        $contact->save();

        return response()->json(['contactId' => $contact->id]);
    }
}
