<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Expense extends Model
{
  use SoftDeletes;

  protected $fillable = [
    'number',
    'date',
    'title',
    'description',
    'currency',
    'amount',
  ];

  protected $appends = ['dateFormated'];

  /**
   * Mutator 'date'
   */

  public function setDateAttribute($value)
  {
    $this->attributes['date'] = \Carbon\Carbon::parse($value)->format('Y.m.d');
  }

  /**
   * Mutator 'date'
   */

  public function getDateFormatedAttribute()
  {
    return \Carbon\Carbon::parse($this->date)->format('d.m.Y');
  }

}
