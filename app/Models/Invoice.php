<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    protected $table = 'invoices';

    protected $fillable = [
        'number',
        'title',
        'total',
        'vat',
        'grandtotal',
        'date',
        'date_due',
        'date_paid',
        'state_id',
        'client_id',
        'processed',
        'remarks'
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
     * Relation 'state'
     */
    public function state()
    {
        return $this->hasOne('App\Models\InvoiceState', 'id', 'state_id');
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
     * Mutator 'date_paid'
     */

    public function setDatePaidAttribute($value)
    {
        $this->attributes['date_paid'] = $value ? \Carbon\Carbon::parse($value)->format('Y.m.d') : NULL;
    }

}
