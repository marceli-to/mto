<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{
    protected $table = 'contacts';

    protected $fillable = [
        'name',
        'firstname',
        'email',
        'phone',
        'client_id'
    ];

    /**
     * Relation 'client'
     */
    public function client()
    {
        return $this->belongsTo('App\Models\Client');
    }
}
