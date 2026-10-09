<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A saved liquidity scenario. Invoices and projects are stored by id only, so
 * their amounts are always resolved live when the snapshot is loaded.
 */
class LiquiditySnapshot extends Model
{
    protected $fillable = [
        'name',
        'data',
    ];

    protected $casts = [
        'data' => 'array',
    ];
}
