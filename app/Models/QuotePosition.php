<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuotePosition extends Model
{
    protected $fillable = [
        'quote_section_id',
        'description',
        'amount',
        'sort_order',
    ];

    public function section()
    {
        return $this->belongsTo(QuoteSection::class, 'quote_section_id');
    }
}
