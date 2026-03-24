<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuoteSection extends Model
{
    protected $fillable = [
        'quote_id',
        'title',
        'total_label',
        'sort_order',
    ];

    public function quote()
    {
        return $this->belongsTo(Quote::class);
    }

    public function positions()
    {
        return $this->hasMany(QuotePosition::class)->orderBy('sort_order');
    }

    public function getTotalAttribute()
    {
        return $this->positions->sum('amount');
    }
}
