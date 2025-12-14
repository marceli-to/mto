<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoicePosition extends Model
{
    protected $fillable = [
        'periode',
        'description',
        'rate',
        'hours',
        'amount',
        'is_flat',
        'is_fee',
        'invoice_id',
    ];

    /**
     * Relation 'client'
     */
    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }
}
