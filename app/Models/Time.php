<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Time extends Model
{
    protected $table = 'times';

    protected $fillable = [
        'task',
        'date',
        'timeStart',
        'timeEnd',
        'minutes',
        'project_id',
        'is_billable',
    ];

    /**
     * Relation 'project'
     */
    
    public function project()
    {
        return $this->hasOne('App\Models\Project', 'id', 'project_id');
    }

    /**
     * Mutator 'date'
     */

    public function setDateAttribute($value)
    {
        $this->attributes['date'] = \Carbon\Carbon::parse($value)->format('Y.m.d');
    }

    /**
     * Mutator 'timeStart'
     */

    public function setTimeStartAttribute($value)
    {
        $this->attributes['timeStart'] = \Carbon\Carbon::parse($value)->format('H:i:s');
    }

    /**
     * Mutator 'timeEnd'
     */

    public function setTimeEndAttribute($value)
    {
        $this->attributes['timeEnd'] = \Carbon\Carbon::parse($value)->format('H:i:s');
    }

    /**
     * Mutator 'timeSpent'
     */

    public function setMinutesAttribute($value)
    {
        $startTime  = \Carbon\Carbon::parse($this->timeStart);
        $finishTime = \Carbon\Carbon::parse($this->timeEnd);
        $timeSpent  = $finishTime->diffInMinutes($startTime);
        $this->attributes['minutes'] = $timeSpent;
    }
}
