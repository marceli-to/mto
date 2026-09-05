<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class Invoice extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'number',
        'title',
        'total',
        'vat',
        'vat_rate',
        'grandtotal',
        'date',
        'date_due',
        'date_paid',
        'state_id',
        'client_id',
        'processed',
        'remarks',
        'text',
        'has_rate_increase_notice',
        'is_reminder',
        'reminder_level'
    ];

    protected $casts = [
        'has_rate_increase_notice' => 'boolean',
        'is_reminder' => 'boolean',
    ];

    /**
     * Relation 'contacts'
     */
    public function positions()
    {
        return $this->hasMany(InvoicePosition::class);
    }

    /**
     * Relation 'client'
     */
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Relation 'state'
     */
    public function state()
    {
        return $this->belongsTo(InvoiceState::class, 'state_id');
    }

    /**
     * Generate invoice number based on year and ID
     */
    public function generateNumber(): void
    {
        $this->number = date('y') . '.' . str_pad($this->id, 4, '0', STR_PAD_LEFT);
        $this->save();
    }

    /**
     * Recalculate total / vat / grandtotal from the invoice's current positions.
     * Mirrors the frontend computation (VAT rounded to 0.05). Persists the invoice.
     */
    public function recalculateTotal(): void
    {
        $total = (float) $this->positions()->sum('amount');
        $vat = ceil(($total / 100 * (float) ($this->vat_rate ?? 0)) * 20) / 20;

        $this->total = $total;
        $this->vat = $vat;
        $this->grandtotal = $total + $vat;
        $this->save();
    }

    /**
     * Scope: exclude cancelled invoices
     */
    public function scopeNotCancelled(Builder $query): Builder
    {
        return $query->where('state_id', '!=', InvoiceState::CANCELLED);
    }

    /**
     * Scope: paid or closed invoices
     */
    public function scopePaid(Builder $query): Builder
    {
        return $query->whereIn('state_id', InvoiceState::PAID_STATES);
    }

    /**
     * Scope: billable invoices (pending, paid, closed)
     */
    public function scopeBillable(Builder $query): Builder
    {
        return $query->whereIn('state_id', InvoiceState::BILLABLE_STATES);
    }

    /**
     * Check if invoice is cancelled
     */
    public function isCancelled(): bool
    {
        return $this->state_id === InvoiceState::CANCELLED;
    }

    /**
     * Check if invoice is pending
     */
    public function isPending(): bool
    {
        return $this->state_id === InvoiceState::PENDING;
    }

    /**
     * Check if invoice is paid (paid or closed)
     */
    public function isPaid(): bool
    {
        return in_array($this->state_id, InvoiceState::PAID_STATES);
    }

    /**
     * Get fiscal year date range (Jan 26 to Jan 25 of next year)
     */
    public static function fiscalYearRange(int $year): array
    {
        return [
            Carbon::create($year, 1, 26)->startOfDay(),
            Carbon::create($year + 1, 1, 25)->endOfDay(),
        ];
    }

    /**
     * Mutator 'date'
     */

    public function setDateAttribute($value)
    {
        $this->attributes['date'] = \Carbon\Carbon::parse($value)->format('Y.m.d');
    }

    /**
     * Mutator 'date_due'
     */

    public function setDateDueAttribute($value)
    {
        $this->attributes['date_due'] = \Carbon\Carbon::parse($value)->format('Y.m.d');
    }

    /**
     * Mutator 'date_paid'
     */

    public function setDatePaidAttribute($value)
    {
        $this->attributes['date_paid'] = $value ? \Carbon\Carbon::parse($value)->format('Y.m.d') : NULL;
    }

}
