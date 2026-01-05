<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'number',
        'title',
        'total',
        'vat',
        'vat_rate',
        'grandtotal',
        'date',
        'date_due',
        'date_paid',
        'state_id',
        'client_id',
        'processed',
        'remarks',
        'text',
        'has_rate_increase_notice'
    ];

    protected $casts = [
        'has_rate_increase_notice' => 'boolean',
    ];

    /**
     * Relation 'contacts'
     */
    public function positions()
    {
        return $this->hasMany(InvoicePosition::class);
    }

    /**
     * Relation 'client'
     */
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Relation 'state'
     */
    public function state()
    {
        return $this->belongsTo(InvoiceState::class, 'state_id');
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
