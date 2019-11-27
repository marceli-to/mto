<?php
namespace App\Http\Controllers\Api;
use App\Services\MediaService;
use App\Models\Client;
use App\Http\Resources\ClientCollection;
use App\Http\Requests\ClientStoreRequest;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    protected $mediaService;

    protected $client;
    
    /**
     * Constructor
     * 
     * @param MediaService $mediaService
     * @param Client $client
     */

    public function __construct(MediaService $mediaService, Client $client)
    {
        $this->mediaService = $mediaService;
        $this->client = $client;
    }

    /**
     * Get all records
     *
     * @return \Illuminate\Http\Response
     */

    public function get()
    {
        return new ClientCollection($this->client->orderBy('name', 'ASC')->get());
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    
    public function store(ClientStoreRequest $request)
    {   
        $client = new Client($request->all());
        $client->save();
        return response()->json(['clientId' => $client->id]);
    }

    /**
     * Edit a specified resource.
     *
     * @param Client $client
     * @return \Illuminate\Http\Response
     */
    public function edit(Client $client)
    {
        return response()->json($client);
    }

    /**
     * Update the status of the specified resource.
     *
     * @param Client $client
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function update(Client $client, ClientStoreRequest $request)
    {
        $client->update($request->all());
        return response()->json('successfully updated');
    }

    /**
     * Clone a specified resource.
     *
     * @param  Client $client
     * @return \Illuminate\Http\Response
     */
    public function clone(Client $client)
    {
        $clone = $client->replicate();
        $clone->name    = $client->name . ' (copy)';
        $clone->acronym = '';
        $clone->publish = 0;
        $clone->save();
        return response()->json($clone);
    }

    /**
     * Update the status of the specified resource.
     *
     * @param  Client $client
     * @return \Illuminate\Http\Response
     */
    public function status(Client $client)
    {
        $client->publish = $client->publish == 0 ? 1 : 0;
        $client->save();
        return response()->json($client->publish);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  Client $client
     * @return \Illuminate\Http\Response
     */
    public function destroy(Client $client)
    {
        $client->delete();
        return response()->json('successfully deleted');
    }
}
