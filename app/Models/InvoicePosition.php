<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoicePosition extends Model
{
    protected $table = 'invoice_positions';

    protected $fillable = [
        'periode',
        'description',
        'rate',
        'hours',
        'amount',
        'is_flat',
        'invoice_id',
    ];

    /**
     * Relation 'client'
     */
    public function invoice()
    {
        return $this->hasOne('App\Models\Invoice', 'id', 'invoice_id');
    }
}
