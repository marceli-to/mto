<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Quote extends Model
{
    use SoftDeletes;

    const DRAFT = 'draft';
    const SENT = 'sent';
    const ACCEPTED = 'accepted';
    const DECLINED = 'declined';

    const STATUSES = [self::DRAFT, self::SENT, self::ACCEPTED, self::DECLINED];

    protected $fillable = [
        'number',
        'label',
        'title',
        'date',
        'client_id',
        'status',
        'intro_greeting',
        'intro_text',
        'vat_rate',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function sections()
    {
        return $this->hasMany(QuoteSection::class)->orderBy('sort_order');
    }

    public function pages()
    {
        return $this->hasMany(QuotePage::class)->orderBy('sort_order');
    }

    public function generateNumber(): void
    {
        $this->number = date('y') . '.' . str_pad($this->id, 4, '0', STR_PAD_LEFT);
        $this->save();
    }

    public function setDateAttribute($value)
    {
        $this->attributes['date'] = \Carbon\Carbon::parse($value)->format('Y.m.d');
    }
}
