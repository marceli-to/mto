<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuoteSection extends Model
{
    protected $fillable = [
        'quote_id',
        'title',
        'total_label',
        'show_total',
        'show_vat_note',
        'currency',
        'sort_order',
    ];

    protected $casts = [
        'show_total' => 'boolean',
        'show_vat_note' => 'boolean',
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
        return $this->positions->whereNull('amount_text')->sum('amount');
    }

    /**
     * Amounts as printed: narrow spaces between thousands, the currency in
     * front unless it is CHF (the VAT note already says so).
     */
    public function formatAmount($amount): string
    {
        $formatted = number_format((float) $amount, 2, '.', "\u{202F}");

        return $this->currency && $this->currency !== 'CHF'
            ? $this->currency . ' ' . $formatted
            : $formatted;
    }
}
