<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuotePage extends Model
{
    const TEXT = 'text';
    const PRICES = 'prices';

    const TYPES = [self::TEXT, self::PRICES];

    protected $fillable = [
        'quote_id',
        'type',
        'content',
        'sort_order',
    ];

    public function quote()
    {
        return $this->belongsTo(Quote::class);
    }
}
