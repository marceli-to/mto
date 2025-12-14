<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Client extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'acronym',
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
        return $this->hasMany(Contact::class);
    }

    /**
     * Mutator 'acronym'
     */

    public function setAcronymAttribute($value)
    {
        $this->attributes['acronym'] = strtoupper($value);
    }
}
