<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    protected $table = 'clients';

    protected $fillable = [
        'name',
        'byline',
        'street',
        'zip',
        'city',
    ];

    /**
     * Relation 'contacts'
     */
    public function contacts()
    {
        return $this->hasMany('App\Models\Contact');
    }
}
