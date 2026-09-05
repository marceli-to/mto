<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends Model
{
    use SoftDeletes;

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
        return $this->belongsTo(Client::class);
    }

    /**
     * Relation 'rateModel' — the Rate referenced by rate_id.
     * Named to avoid colliding with the legacy free-text 'rate' column.
     */
    public function rateModel()
    {
        return $this->belongsTo(Rate::class, 'rate_id');
    }

    /**
     * Relation 'timeEntries'
     */
    public function timeEntries()
    {
        return $this->hasMany(TimeEntry::class);
    }
}
