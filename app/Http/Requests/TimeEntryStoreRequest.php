<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class TimeEntryStoreRequest extends FormRequest
{
    /** Entries are booked in quarter-hour steps. */
    private const STEP_MINUTES = 15;

    public function authorize()
    {
        return true;
    }

    /**
     * Activity entries carry no project and are never billable. Rates always come
     * from the project, and `hours` is derived from the time span — neither is
     * taken from the client. Normalize all of it before the rules run.
     */
    protected function prepareForValidation()
    {
        $data = ['rate' => null];

        if ($this->filled('activity')) {
            $data['project_id']  = null;
            $data['is_billable'] = false;
        }

        $from = static::normalizeTime($this->input('time_from'));
        $to   = static::normalizeTime($this->input('time_to'));

        $data['time_from'] = $from;
        $data['time_to']   = $to;

        $minutes = ($from !== null && $to !== null)
            ? static::toMinutes($to) - static::toMinutes($from)
            : 0;
        $data['hours'] = $minutes > 0 ? round($minutes / 60, 2) : null;

        $this->merge($data);
    }

    public function rules()
    {
        return [
            'project_id'  => 'nullable|exists:projects,id|required_without:activity',
            'activity'    => 'nullable|string|required_without:project_id|in:' . implode(',', config('timetracking.activities')),
            'is_billable' => 'boolean',
            'date'        => 'required|date',
            'time_from'   => 'required|date_format:H:i',
            'time_to'     => 'required|date_format:H:i',
            'hours'       => 'required|numeric|min:0.25',
            'description' => 'nullable|string',
            'rate'        => 'nullable|numeric|min:0',
        ];
    }

    public function withValidator(Validator $validator)
    {
        $validator->after(function (Validator $validator) {
            // Exactly one of project_id / activity.
            if ($this->filled('project_id') && $this->filled('activity')) {
                $validator->errors()->add('activity', 'An entry cannot have both a project and an activity.');
            }

            // A zero or negative span would otherwise surface as a confusing "hours is required".
            $from = $this->input('time_from');
            $to   = $this->input('time_to');
            if ($from && $to && static::toMinutes($to) <= static::toMinutes($from)) {
                $validator->errors()->add('time_to', 'End time must be after the start time.');
            }
        });
    }

    public function messages()
    {
        return [
            'project_id.required_without' => 'Select a project or an activity.',
            'activity.required_without'   => 'Select a project or an activity.',
            'activity.in'                 => 'Unknown activity.',
            'time_from.required'          => 'Enter a start time.',
            'time_from.date_format'       => 'Use a time like 08.30.',
            'time_to.required'            => 'Enter an end time.',
            'time_to.date_format'         => 'Use a time like 10.15.',
        ];
    }

    /**
     * Accepts the shapes people actually type — "8.30", "08:30", "0830", "8" — and
     * returns "HH:MM" snapped to the nearest quarter hour. Null if unparseable.
     */
    public static function normalizeTime($value): ?string
    {
        if (!is_string($value) && !is_numeric($value)) {
            return null;
        }

        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        if (preg_match('/^(\d{1,2})\s*[.:,h]\s*(\d{1,2})$/', $value, $m)) {
            $hours   = (int) $m[1];
            // A single digit reads as tens of minutes: "8.3" is half past eight.
            $minutes = (int) str_pad($m[2], 2, '0', STR_PAD_RIGHT);
        } elseif (preg_match('/^(\d{1,2})(\d{2})$/', $value, $m)) {
            $hours   = (int) $m[1];
            $minutes = (int) $m[2];
        } elseif (preg_match('/^(\d{1,2})$/', $value, $m)) {
            $hours   = (int) $m[1];
            $minutes = 0;
        } else {
            return null;
        }

        if ($hours > 23 || $minutes > 59) {
            return null;
        }

        $total = (int) round(($hours * 60 + $minutes) / self::STEP_MINUTES) * self::STEP_MINUTES;
        $total = min($total, 24 * 60 - self::STEP_MINUTES); // 23:59 must not roll over to 24:00

        return sprintf('%02d:%02d', intdiv($total, 60), $total % 60);
    }

    /** "08:30" -> 510. */
    public static function toMinutes(string $time): int
    {
        [$hours, $minutes] = array_pad(explode(':', $time), 2, 0);

        return ((int) $hours) * 60 + (int) $minutes;
    }
}
