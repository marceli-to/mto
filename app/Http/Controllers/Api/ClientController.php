<?php

namespace App\Http\Controllers\Api;

use App\Models\Client;
use App\Http\Controllers\Controller;
use App\Http\Requests\ClientStoreRequest;
use App\Actions\Client\Get as GetAction;
use App\Actions\Client\Show as ShowAction;
use App\Actions\Client\Store as StoreAction;
use App\Actions\Client\Update as UpdateAction;
use App\Actions\Client\Delete as DeleteAction;
use App\Actions\Client\Duplicate as DuplicateAction;
use App\Actions\Client\UpdateStatus as UpdateStatusAction;
use App\Actions\Client\CheckUniqueAcronym as CheckUniqueAcronymAction;

class ClientController extends Controller
{
    public function get()
    {
        return (new GetAction)->execute();
    }

    public function store(ClientStoreRequest $request)
    {
        return (new StoreAction)->execute($request);
    }

    public function edit(Client $client)
    {
        return (new ShowAction)->execute($client);
    }

    public function update(Client $client, ClientStoreRequest $request)
    {
        return (new UpdateAction)->execute($client, $request);
    }

    public function duplicate(Client $client)
    {
        return (new DuplicateAction)->execute($client);
    }

    public function status(Client $client)
    {
        return (new UpdateStatusAction)->execute($client);
    }

    public function destroy(Client $client)
    {
        return (new DeleteAction)->execute($client);
    }

    public function uniqueAcronym($acronym)
    {
        return (new CheckUniqueAcronymAction)->execute($acronym);
    }
}
