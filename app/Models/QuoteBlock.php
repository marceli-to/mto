<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A reusable piece of quote text (terms, hosting requirements, about me…)
 * that gets copied into a quote as a text page.
 */
class QuoteBlock extends Model
{
    protected $fillable = [
        'title',
        'content',
        'is_default',
        'sort_order',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];
}
