<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TimeEntry extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'project_id', 'activity', 'is_billable', 'date', 'hours', 'description', 'rate',
        'invoice_id', 'invoice_position_id',
    ];

    protected $casts = [
        'date'        => 'date',
        'hours'       => 'decimal:2',
        'rate'        => 'decimal:2',
        'is_billable' => 'boolean',
    ];

    public function project()         { return $this->belongsTo(Project::class); }
    public function invoice()         { return $this->belongsTo(Invoice::class); }
    public function invoicePosition() { return $this->belongsTo(InvoicePosition::class); }

    /** Resolved billing rate: entry override, else the project's rate. Activity/non-billable entries have no rate. */
    public function resolvedRate(): float
    {
        if (!$this->is_billable || is_null($this->project_id)) {
            return 0.0;
        }
        if (!is_null($this->rate)) {
            return (float) $this->rate;
        }
        return (float) (optional($this->project->rateModel)->amount ?? 0);
    }

    /** Gross value of this entry before budget capping. 0 for non-billable/activity entries. */
    public function value(): float
    {
        return (float) $this->hours * $this->resolvedRate();
    }

    public function isActivity(): bool { return is_null($this->project_id) && !is_null($this->activity); }

    public function scopeBillable($q) { return $q->where('is_billable', true)->whereNotNull('project_id'); }
    public function scopeUnbilled($q) { return $q->whereNull('invoice_id'); }

    public function isBilled(): bool { return !is_null($this->invoice_id); }
}
