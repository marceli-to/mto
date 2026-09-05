# Time Tracking — Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Log time as manual entries — either **project work** (project + date + hours + description, optional per-entry rate override, optionally non-billable) or a **non-billable activity** (admin/gym/… via config-defined chips) — presented as a collapsible **list of days** with header **revenue stat cards** (selected day / current week / current month). Day totals reflect all logged time (so the day adds up to ~8h); revenue is the budget-capped value of **billable project** time only. Collection projects can generate invoice positions from their time entries; flat-rate projects continue to be billed with manually-added positions.

**Architecture:** A new standalone **Time** section. Backend follows the existing thin-controller + **Action pattern** (`App\Actions\TimeEntry\{Get,Show,Store,Update,Delete,Bill,Unbill}`). Each `time_entry` belongs to a `project`. The `Get` action returns entries **grouped by day** (newest first) plus the three **revenue aggregates**, all computed by a single **revenue engine** so day/week/month stay consistent. Billing generates **one `InvoicePosition` per entry** and links it back to the entry; unbilling reverses it. Manual invoicing is untouched.

**Tech Stack:** Laravel 12 (Action pattern, Eloquent, soft deletes), PHP 8.2+, Vue 3 Composition API (composables, flyout forms), Laravel Sanctum (`auth:sanctum`).

---

## Domain rules (locked with user)

**Entry model**
- **Manual entry only** — no live timer.
- **Project only** granularity — no task/category dimension.
- Rate: **per-entry override**, falls back to the project's rate (`rate_id` → `Rate.amount`).
- **Single user** — no `user_id`.
- Time can be logged on **any** project (collection or flat-rate).
- **Two kinds of entry**: a **project entry** (`project_id` set, `activity` null) OR an **activity entry** (`activity` string set, `project_id` null). Exactly one of the two — enforced by validation.
- **`is_billable`** (bool, default `true`) is a **free toggle on any entry**. A project entry can be marked non-billable (goodwill/write-off). Activity entries force `is_billable = false`.

**Activities (recurring non-billable time: admin, gym, lunch, …)**
- Purpose: fill out the day so the day-total reflects real elapsed time; **no per-activity reporting needed**.
- Modeled as a **denormalized string** on the entry (`activity`), **not** a relational entity — no `activities` table, no FK, no reporting joins.
- The pick list of chips is defined in a **config array** (`config/timetracking.php` → `'activities' => ['Admin', 'Gym', 'Lunch', …]`). Adding/renaming a chip is a config edit (no runtime `+` management — user does not need it).
- In the form, clicking an activity chip sets `activity`, nulls `project_id`, and forces `is_billable = false`.
- Activity entries: count toward **day total hours**, contribute **0 revenue**, and are **never billable**.

**Project modes** (driven by the existing `is_collection` flag)

| Mode | `is_collection` | Budget | Billing |
|---|---|---|---|
| **Collection** | `true` | optional (unset/0 = uncapped) | Invoice positions **generated from time entries** |
| **Flat-rate** | `false` | **required** (= the fixed price / ceiling) | Invoice positions **added manually** (current behavior, untouched) |

- New validation: when `is_collection == false`, `budget` is **required and > 0**.
- Only **collection** projects may bill *from* time entries.

**Revenue rule (the core logic)**
- **Revenue = the in-budget slice of logged time.** For each project, entries consume budget **cumulatively, oldest-entry-first** (ties broken by `id`). Value of an entry = `hours × resolvedRate`. The portion of an entry's value that fits under the project's remaining budget is **revenue**; the portion above the budget is **over-budget** (not revenue).
- Budget caps in **both** modes. A collection project with **no budget** (null/0) is **uncapped** — all its logged value is revenue.
- **Period revenue** (day / week / month) = the sum, across projects, of the in-budget slice attributable to entries **dated within that period**, given budget already consumed by all **earlier** entries. Because consumption is one cumulative oldest-first timeline, day ⊆ week ⊆ month stay consistent.

> **Worked example** — Project Alpha, budget 5'000, rate 200/h:
> Week 1: 20h → value 4'000, budget free → **revenue 4'000**.
> Week 2: 10h → value 2'000, only 1'000 budget left → **revenue 1'000**, 1'000 over-budget.
> Week 3: 5h → value 1'000, budget exhausted → **revenue 0**.

**Billing**
- Backend built now: **one `InvoicePosition` per entry**; stamp `invoice_id` + `invoice_position_id` back onto the entry. Unbill reverses (delete positions, clear links). Billed entries are **locked** from edit/delete.
- **Billing UI is deferred** to a later pass. First cut = logging + day list + stat cards. (Backend billing actions + routes are still built and tested via API.)

**Layout**
- **Header:** three revenue **stat cards** — selected day / current week / current month. "Selected day" defaults to today and updates when a day is opened in the list. A **plus icon** creates a new entry (matches the invoices pattern).
- **Body:** collapsible **list of days**, newest first, **only days with entries**. Day header shows weekday + date + day total hours (e.g. "Friday, 4.9. — 5.75h"). **Today open by default**, others collapsed. Entries within a day show project · description · hours · (edit/delete) · billed badge.

---

## Verified codebase facts (grounding for implementation)

- `projects.budget` is `decimal(8,2)`, nullable, default `0.00`.
- `Project` has `rate_id`; `Rate` has `amount`. (Project also has a legacy free-text `rate` column — do **not** confuse the two; the FK relation will be named `rateModel`.)
- **Invoice totals are computed explicitly**, not via observer: `App\Actions\Invoice\Update` sums `position.amount` into `invoice->total` and saves. There is **no shared recompute helper** today. `InvoiceObserver::deleting` cascades `positions()->delete()`.
- Invoice relations: `positions()` (hasMany InvoicePosition). Invoice money fields: `total`, `grandtotal`.
- `InvoicePosition` fillable: `periode, description, rate, hours, amount, is_flat, is_fee, invoice_id`.
- Thin controllers delegate to Actions (see `ExpenseController`). Routes live under the `auth:sanctum` group in `routes/api.php` with `entity/verb` naming.
- Frontend: Vue 3, router at `resources/js/spa/router/index.js`, composables `useApi`/`useCurrency`/`useToast`, forms shown as flyouts. Build with `npm run build` → `public/build/`.

---

### Task 1: Database migration — `time_entries` table

**Files:** Create `database/migrations/2026_09_05_000001_create_time_entries_table.php`

```php
Schema::create('time_entries', function (Blueprint $table) {
    $table->id();
    $table->foreignId('project_id')->nullable()->constrained()->cascadeOnDelete(); // null for activity entries
    $table->string('activity')->nullable();      // set for activity entries (admin/gym/…); mutually exclusive w/ project_id
    $table->boolean('is_billable')->default(true);
    $table->date('date');
    $table->decimal('hours', 6, 2);              // e.g. 1.25
    $table->text('description')->nullable();
    $table->decimal('rate', 10, 2)->nullable();  // per-entry override; null = inherit project rate
    $table->foreignId('invoice_id')->nullable()->constrained()->nullOnDelete();
    $table->foreignId('invoice_position_id')->nullable()->constrained()->nullOnDelete();
    $table->timestamps();
    $table->softDeletes();
    $table->index(['project_id', 'date', 'id']);  // supports oldest-first cumulative ordering
    $table->index('invoice_id');
});
```

Run `php artisan migrate`.

---

### Task 2: Model — `TimeEntry`

**Files:** Create `app/Models/TimeEntry.php`

```php
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

    /** Resolved billing rate: entry override, else the project's rate. Activity/no-project entries have no rate. */
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
    public function scopeBillable($q)  { return $q->where('is_billable', true)->whereNotNull('project_id'); }
    public function scopeUnbilled($q)  { return $q->whereNull('invoice_id'); }
    public function isBilled(): bool   { return !is_null($this->invoice_id); }
}
```

---

### Task 3: Relate Project → Rate / TimeEntry

**Files:** Modify `app/Models/Project.php`

```php
public function rateModel()   { return $this->belongsTo(Rate::class, 'rate_id'); }
public function timeEntries() { return $this->hasMany(TimeEntry::class); }
```

**Verification:** `php artisan tinker` → `App\Models\Project::first()->rateModel`.

---

### Task 4: Revenue engine (the heart of the feature)

**Files:** Create `app/Actions/TimeEntry/RevenueEngine.php` (plain service class, not tied to a request).

Responsibility: given a set of entries, apply the **cumulative, oldest-first, per-project, budget-capped** rule and expose:
- `perEntryRevenue(Collection $entries): array` — map of `entry_id => ['value' => x, 'revenue' => y, 'over_budget' => z]`.
- `periodRevenue(Carbon $from, Carbon $to): float` — revenue attributable to entries dated in `[from, to]`.

**Only billable project entries participate.** Filter to `is_billable = true AND project_id IS NOT NULL` before the walk (i.e. `TimeEntry::billable()`). Activity entries and non-billable project entries are excluded up front → 0 revenue, and they do **not** consume budget.

**Algorithm:**
1. Load **all billable project entries** (across all time) for the projects in question, ordered by `project_id`, then `date ASC`, then `id ASC`.
2. Walk each project's entries oldest→newest, tracking `consumed` budget.
   - `remaining = budget === null || budget == 0 ? INF (if collection) : budget - consumed`
   - For a flat-rate project, budget is guaranteed set (validation), so `remaining = budget - consumed`.
   - `revenue = min(entry.value, max(0, remaining))`; `consumed += entry.value` (track full value so later entries see true consumption); `over_budget = entry.value - revenue`.
3. For a period figure, sum `revenue` of entries whose `date` ∈ period. (Cumulative state is built from the full timeline, so a period sees the correct remaining budget.)

> **Uncapped case:** a collection project with null/0 budget → every entry's `revenue == value`.
> **Note:** capping is on **value**, oldest-first — an entry straddling the budget boundary is split (part revenue, part over-budget).

**Verification:** unit test with the worked example above (3 weeks / 5'000 budget / 200 rate → 4'000, 1'000, 0).

---

### Task 5: Form request — validation (incl. flat-rate budget rule)

**Files:**
- Create `config/timetracking.php` — `return ['activities' => ['Admin', 'Gym', 'Lunch']];` (seed with the user's current recurring activities; adjust the list as needed).
- Create `app/Http/Requests/TimeEntryStoreRequest.php`
- Modify `app/Http/Requests/ProjectStoreRequest.php`

**TimeEntry rules:**
```php
'project_id'  => 'nullable|exists:projects,id|required_without:activity',
'activity'    => 'nullable|string|required_without:project_id|in:'.implode(',', config('timetracking.activities')),
'is_billable' => 'boolean',
'date'        => 'required|date',
'hours'       => 'required|numeric|min:0.01',
'description' => 'nullable|string',
'rate'        => 'nullable|numeric|min:0',
```
Add a `withValidator` check enforcing **exactly one** of `project_id` / `activity` (not both). In the Store/Update actions (or a `prepareForValidation`), when `activity` is set: force `project_id = null`, `is_billable = false`, `rate = null`. Validate `activity` against the config list so only known chips are accepted.

**Project rule (new):** require a budget for flat-rate projects.
```php
'budget' => 'nullable|numeric|min:0|required_if:is_collection,false|prohibited_if:...'  // require_if only
// Effective: when is_collection is false -> budget required and > 0.
```
Use `required_if:is_collection,false` plus a `min:0.01` applied conditionally (a `withValidator` closure, since `is_collection` is boolean-ish from the SPA — normalize to bool first). Confirm the SPA sends `is_collection` as a real boolean.

---

### Task 6: Actions — CRUD

**Files:** Create `app/Actions/TimeEntry/{Get,Show,Store,Update,Delete}.php`

**Get** — the primary read for the Time screen. Accepts optional `project_id` filter and an anchor date (default today) for the "selected day" card.
- Load entries (newest first) with `project.rateModel`.
- Group into **days**: `[{ date, weekday_label, total_hours, entries: [...] }]`, newest day first, only days with entries. **`total_hours` counts all entries** (project + activity + non-billable) so the day reflects real elapsed time.
- Per entry include a display **label** (project name **or** `activity`), `is_billable`, `is_activity`, `resolvedRate`, `value`, `revenue`, `over_budget` (from the **RevenueEngine**; 0 for activity/non-billable), and `is_billed` + `invoice_id`.
- Return `stats: { day, week, month }` revenue via `RevenueEngine::periodRevenue()` for: the selected day, the current ISO week, the current calendar month.
- Shape:
  ```json
  { "days": [...], "stats": { "day": 1150.0, "week": 4800.0, "month": 18200.0 } }
  ```

**Store / Update** — `new TimeEntry($request->all())` / `$entry->update($request->all())`. Guard: a **billed** entry (has `invoice_id`) may not be edited/deleted → `422` with a clear message.

**Delete** — soft delete; block if billed.

---

### Task 7: Actions — Billing (backend only; UI deferred)

**Files:** Create `app/Actions/TimeEntry/{Bill,Unbill}.php`

**Bill** — input: `invoice_id` + `time_entry_ids[]`.
- In a transaction: load target invoice and the selected **unbilled** entries (skip & report already-billed). Optionally assert all belong to **collection** projects.
- For each entry create one `InvoicePosition`: `invoice_id`, `periode` = formatted entry date (match existing `periode` convention — **confirm format**), `description`, `hours`, `rate = resolvedRate()`, `amount = value()`, `is_flat = false`, `is_fee = false`. Stamp `invoice_id` + `invoice_position_id` back onto the entry.
- **Recompute invoice total** the same way `Invoice\Update` does: `invoice->total = invoice->positions()->sum('amount')` then save. (See note below.)

**Unbill** — input: `time_entry_ids[]` (or `invoice_id`).
- Transaction: delete the linked `InvoicePosition`(s), null `invoice_id` + `invoice_position_id` on each entry, recompute invoice total.

> **Refactor note (do first):** Invoice total logic is currently inline in `Invoice\Update`. Extract it into a small reusable method — e.g. `Invoice::recalculateTotal()` on the model (sum `positions.amount` → `total`, save) — and have both `Invoice\Update` and the new Bill/Unbill call it. Avoids two divergent total computations. Keep `grandtotal` handling identical to today.

---

### Task 8: Controller + Routes

**Files:** Create `app/Http/Controllers/Api/TimeEntryController.php`; modify `routes/api.php`.

Thin controller like `ExpenseController`: `get()`, `store(TimeEntryStoreRequest)`, `edit(TimeEntry)`, `update(...)`, `destroy(TimeEntry)`, `bill(Request)`, `unbill(Request)`.

```php
Route::get('time-entries/get', [TimeEntryController::class, 'get']);
Route::post('time-entry/create', [TimeEntryController::class, 'store']);
Route::get('time-entry/edit/{timeEntry}', [TimeEntryController::class, 'edit']);
Route::post('time-entry/update/{timeEntry}', [TimeEntryController::class, 'update']);
Route::delete('time-entry/destroy/{timeEntry}', [TimeEntryController::class, 'destroy']);
Route::post('time-entries/bill', [TimeEntryController::class, 'bill']);      // no UI yet
Route::post('time-entries/unbill', [TimeEntryController::class, 'unbill']);  // no UI yet
```

**Verification:** `php artisan route:list --path=time`

---

### Task 9: Frontend — Time section (day list + stat cards)

**Files:**
- Modify `resources/js/spa/router/index.js` — add `{ path: '/time', name: 'time', component: TimeList }`.
- Create `resources/js/spa/components/time/TimeList.vue`
- Create `resources/js/spa/components/time/TimeForm.vue`
- Modify the primary nav component (locate it — links to Projects/Expenses) to add a **Time** link.

**`TimeList.vue`:**
- **Header:** three stat cards (selected day / this week / this month) bound to `stats` from the API, formatted with `useCurrency`. A **plus icon** opens `TimeForm` as a flyout (match invoices).
- **Body:** collapsible day blocks from `days`. Header = weekday + `D.M.` + total hours. **Today expanded by default**; opening a day sets it as the selected day and re-fetches (or recomputes) the day stat card.
- Each entry row: **label** (project name or activity) · description · hours · resolved amount; a **non-billable badge** (for `is_billable=false`, incl. all activities) and a billed badge; edit/delete actions (disabled when billed). Activity rows are visually distinct (muted) since they carry no revenue.
- **No billing UI in this cut** (checkboxes / "bill selected" come later).
- Uses `useApi`, `useCurrency`, `useToast`.

**`TimeForm.vue`:**
- **Activity chips** at the top from `config('timetracking.activities')` (expose via an API/bootstrap payload or a small `time-entries/config` endpoint). Clicking a chip switches the form to **activity mode**: sets `activity`, hides/clears project + rate, forces `is_billable = false`.
- **Project mode** (default): project (select), rate (optional; placeholder shows inherited project rate), and an **`is_billable` toggle** (default on) so a project entry can be marked non-billable (e.g. an unbilled bugfix on a collection project).
- Common fields: **date**, hours, description. Use the shared **`BaseInput`** with `type="date"` (native date picker) — `<BaseInput v-model="entry.date" label="Date" type="date" />` — matching `ExpenseForm`/`InvoiceForm`/`QuoteForm`. No date-picker or input-mask library (none is installed; the SPA uses native `type="date"` everywhere).
  - **Default to today** for new entries: initialize `entry.date = moment().format('YYYY-MM-DD')` (native `type="date"` requires the `YYYY-MM-DD` string; `moment` is already a dependency). On edit, populate from the existing entry.
- Exactly one of project/activity is active at a time (mirrors backend validation). Create/update via API, then refresh list.

---

### Task 10: Tests + build + manual verification

**Files:** Create `tests/Feature/TimeEntryTest.php` (+ the RevenueEngine unit test from Task 4). Build output.

**Automated:**
- RevenueEngine: worked example + uncapped (no-budget collection) + straddling-boundary split + **non-billable project entry contributes 0 and does not consume budget** + **activity entries excluded**.
- Feature: create/update/delete entry; **project XOR activity validation** (both set → 422, neither → 422); **activity entry forces is_billable=false + project_id=null**; **unknown activity string rejected**; non-billable project entry counts toward day hours but 0 revenue; billed entry is edit/delete-locked (422); bill → one position per entry + invoice total updated + entry linked; unbill → positions removed + total restored.

**Build:** `npm run build`

**Manual (use `/verify`):**
1. Flat-rate project without budget → save blocked (validation).
2. Collection project, budget 5'000, rate 200/h → log 20h then 10h then 5h across three weeks → stat cards / per-entry revenue match 4'000 / 1'000 / 0.
3. Rate override on an entry wins over project rate.
4. Bill collection-project entries via API → positions created, invoice total correct; unbill restores.
5. Day list: today open by default, newest first, day totals correct; opening another day updates the selected-day card.
6. **Activity chip** (e.g. Gym, 1h) → entry saved with no project, non-billable, shows in day total but 0 revenue, muted styling.
7. **Non-billable project entry** (bugfix on a collection project, toggle off) → counts toward day hours, 0 revenue, excluded from billing.

---

---

## Implementation status (as of 2026-09-05)

**All 10 tasks implemented and committed to `master` (local — may be unpushed).** Commits:
- `d621f8a` CLAUDE.md accuracy fixes
- `405659e` this plan doc
- `d5dd0d7` backend (model, RevenueEngine, CRUD, billing)
- `47275dc` frontend + feature tests

**Tests:** 16 passing (5 `tests/Unit/RevenueEngineTest.php` + 11 `tests/Feature/TimeEntryTest.php`). Run: `./vendor/bin/phpunit --filter=RevenueEngineTest` / `--filter=TimeEntryTest`.

**Verified:** backend end-to-end (tinker + feature tests), frontend compiles (`npm run build`). **NOT yet verified:** live UI in a browser — needs a manual click-through of `/time` (add project entry + activity, check day collapsibles + revenue cards, edit/delete).

**Decisions made during build (not in the original plan):**
- `Invoice::recalculateTotal()` was extracted and now recomputes `total`/`vat`/`grandtotal` from persisted positions. `Invoice\Update` was refactored to use it (previously only `total` was recomputed server-side; `vat`/`grandtotal` were trusted from the client). This makes the server authoritative for invoice totals — a behavior change to core invoicing worth regression-checking.
- `periode` on generated positions is formatted `d.m.Y` (matches existing German date strings).
- Bill coalesces null entry description to `''` (invoice_positions.description is NOT NULL).
- `config/timetracking.php` seeds activity chips `['Admin','Gym','Lunch']` — adjust to taste.
- Test DB: SQLite in-memory added to `phpunit.xml` (was previously unconfigured — a real risk, tests would have hit dev MySQL).

**Known pre-existing issue (not caused by this work):** stock `tests/Feature/ExampleTest.php` fails (GETs `/`, expects 200, app redirects to login → 302). Left untouched — decide whether to delete.

**Deferred / remaining work (user notes 2026-09-05):**

1. **Bill / unbill time entry (UI)** — ⏳ *partially done.* Billing a collection project's entries is now available in the **invoice-create flyout** (see #3). Still missing: an **unbill** affordance in the UI (backend `Unbill` exists + tested) and per-entry multi-select billing from the Time screen (not needed if project-level billing suffices — revisit).
2. **Rate handling (set on project, manual)** — both paths already exist: project rate via `rate_id` (Rate picker on `ProjectForm`) and per-entry manual override (rate field in `TimeForm`, falls back to project rate). Verified end-to-end via tinker (override wins, blank inherits). Review item only: confirm the UX is clear in the browser.
3. **Create bill for collection project (ui + backend)** — ✅ **done (2026-09-05).** The invoice-create flyout now has a source toggle: **Manual** or **From collection project**. Selecting an `is_collection` project loads its unbilled billable entries (`GET /api/time-entries/unbilled/{project}`), prefills title + client, and previews one position per entry. On save it creates a draft invoice then calls `time-entries/bill` (positions created server-side, entries linked so they can't be double-billed). New backend: `App\Actions\TimeEntry\UnbilledForProject`. Verified end-to-end via tinker (2 entries → 2 positions, total/grandtotal correct, entries no longer unbilled). **Still needs a browser UI check.**

### Open questions to resolve during implementation
1. **`periode` format** — match how existing InvoicePositions format the period string before writing Bill.
2. **`is_collection` boolean coercion** — confirm the SPA sends a real boolean so the `required_if` budget rule fires correctly; normalize in the request if needed.
3. **Primary nav component** — locate it (not yet identified) to add the Time link.
4. **Selected-day recompute** — decide whether opening a day re-fetches `stats.day` from the server or the client recomputes from already-loaded per-entry revenue (server is source-of-truth; lean server).
