<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    protected $table = 'invoices';

    protected $fillable = [
        'number',
        'title',
        'date',
        'date_due',
        'date_paid',
        'status',
        'client_id',
    ];

    /**
     * Relation 'contacts'
     */
    public function positions()
    {
        return $this->hasMany('App\Models\InvoicePosition');
    }

    /**
     * Relation 'client'
     */
    public function client()
    {
        return $this->hasOne('App\Models\Client', 'id', 'client_id');
    }

    /**
     * Mutator 'date'
     */

    public function setDateAttribute($value)
    {
        $this->attributes['date'] = \Carbon\Carbon::parse($value)->format('Y.m.d');
    }

    /**
     * Mutator 'date_due'
     */

    public function setDateDueAttribute($value)
    {
        $this->attributes['date_due'] = \Carbon\Carbon::parse($value)->format('Y.m.d');
    }

    /**
     * Mutator 'status'
     */

    public function setStatusAttribute($value)
    {
        $this->attributes['status'] = array_search($value, config('invoices.status'));
    }

    /**
     * Accessor 'status'
     */

    public function getStatusAttribute($value)
    {
        return config('invoices.status.' . $value);
    }
}
