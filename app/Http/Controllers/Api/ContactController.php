<?php

namespace App\Http\Controllers\Api;

use App\Models\Contact;
use App\Http\Controllers\Controller;
use App\Http\Requests\ContactStoreRequest;
use App\Actions\Contact\Get as GetAction;
use App\Actions\Contact\Show as ShowAction;
use App\Actions\Contact\Store as StoreAction;
use App\Actions\Contact\Update as UpdateAction;
use App\Actions\Contact\Delete as DeleteAction;

class ContactController extends Controller
{
    public function get($clientId = NULL)
    {
        return (new GetAction)->execute($clientId);
    }

    public function store(ContactStoreRequest $request)
    {
        return (new StoreAction)->execute($request);
    }

    public function edit(Contact $contact)
    {
        return (new ShowAction)->execute($contact);
    }

    public function update(Contact $contact, ContactStoreRequest $request)
    {
        return (new UpdateAction)->execute($contact, $request);
    }

    public function destroy(Contact $contact)
    {
        return (new DeleteAction)->execute($contact);
    }
}
