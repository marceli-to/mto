<?php
namespace App\Http\Controllers\Api;
use App\Models\Contact;
use App\Http\Resources\ContactCollection;
use App\Http\Requests\ContactStoreRequest;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    protected $contact;
    
    /**
     * Constructor
     * 
     * @param Contact $contact
     */

    public function __construct(Contact $contact)
    {
        $this->contact = $contact;
    }

    /**
     * Get all records
     * 
     * @param int $clientId
     * @return \Illuminate\Http\Response
     */

    public function get($clientId = NULL)
    {
        return new ContactCollection($this->contact->orderBy('name', 'ASC')->where('client_id', '=', $clientId)->get());
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    
    public function store(ContactStoreRequest $request)
    {   
        $contact = new Contact($request->all());
        $contact->save();
        return response()->json(['contactId' => $contact->id]);
    }

    /**
     * Edit a specified resource.
     *
     * @param Contact $contact
     * @return \Illuminate\Http\Response
     */
    public function edit(Contact $contact)
    {
        return response()->json($contact);
    }

    /**
     * Update the status of the specified resource.
     *
     * @param Contact $contact
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function update(Contact $contact, ContactStoreRequest $request)
    {
        $contact->update($request->all());
        return response()->json('successfully updated');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  Contact $contact
     * @return \Illuminate\Http\Response
     */
    public function destroy(Contact $contact)
    {
        $contact->delete();
        return response()->json('successfully deleted');
    }
}
