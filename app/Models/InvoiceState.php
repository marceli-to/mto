<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoiceState extends Model
{
    protected $table = 'states';

    // State IDs
    const DRAFT = 1;
    const PENDING = 2;
    const PAID = 3;
    const CLOSED = 5;
    const CANCELLED = 6;

    // Grouped states
    const PAID_STATES = [self::PAID, self::CLOSED];
    const BILLABLE_STATES = [self::PENDING, self::PAID, self::CLOSED];
}
