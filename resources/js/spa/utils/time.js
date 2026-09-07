/**
 * Time-of-day helpers for time entries. Mirrors the server-side normalization in
 * App\Http\Requests\TimeEntryStoreRequest so the field shows exactly what gets stored.
 */

/** Entries are booked in quarter-hour steps. */
const STEP_MINUTES = 15

/**
 * Accepts the shapes people actually type — "8.30", "08:30", "0830", "8" — and returns
 * "HH:MM" snapped to the nearest quarter hour. Returns null if unparseable.
 */
export function normalizeTime (value) {
  if (value === null || value === undefined) return null

  const raw = String(value).trim()
  if (raw === '') return null

  let hours
  let minutes
  let match

  if ((match = raw.match(/^(\d{1,2})\s*[.:,h]\s*(\d{1,2})$/))) {
    hours = parseInt(match[1], 10)
    // A single digit reads as tens of minutes: "8.3" is half past eight.
    minutes = parseInt(match[2].padEnd(2, '0'), 10)
  } else if ((match = raw.match(/^(\d{1,2})(\d{2})$/))) {
    hours = parseInt(match[1], 10)
    minutes = parseInt(match[2], 10)
  } else if ((match = raw.match(/^(\d{1,2})$/))) {
    hours = parseInt(match[1], 10)
    minutes = 0
  } else {
    return null
  }

  if (hours > 23 || minutes > 59) return null

  let total = Math.round((hours * 60 + minutes) / STEP_MINUTES) * STEP_MINUTES
  total = Math.min(total, 24 * 60 - STEP_MINUTES) // 23:59 must not roll over to 24:00

  return String(Math.floor(total / 60)).padStart(2, '0') + ':' + String(total % 60).padStart(2, '0')
}

/** "08:30" -> 510. */
export function toMinutes (time) {
  const [hours, minutes] = String(time).split(':')

  return parseInt(hours, 10) * 60 + parseInt(minutes || '0', 10)
}
