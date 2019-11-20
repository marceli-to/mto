<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    protected $table = 'projects';

    protected $fillable = [
        'name',
        'budget',
        'rate',
        'is_archive',
        'is_collection',
        'rate_id',
        'client_id',
        'principal_id',
    ];

    /**
     * Relation 'client'
     */
    public function client()
    {
        return $this->hasOne('App\Models\Client', 'id', 'client_id');
    }
}
