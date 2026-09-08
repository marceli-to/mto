<?php

namespace App\Console\Commands;

use App\Models\Project;
use App\Models\TimeEntry;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * One-off import of the "Aufwände Marcel" spreadsheet (Jul–Sep 2026).
 *
 * The rows are embedded rather than read from a file so the import can be run on
 * production without shipping the CSV. Rows the sheet marked done without a project
 * id were already billed elsewhere and are not part of this set.
 *
 * The sheet only records a duration, so each entry is laid into the first free
 * quarter-hour slot of its day, starting at 08:00 and packing forward around
 * whatever is already booked. Delete this command once the import has run.
 */
class ImportTimeEntries extends Command
{
    protected $signature = 'timeentries:import
                            {--dry-run : Show what would be imported without writing anything}
                            {--force : Skip the confirmation prompt}';

    protected $description = 'One-off import of the Aufwände Marcel time sheet (Jul–Sep 2026)';

    /** Entries are booked in quarter-hour steps, the first one no earlier than 08:00. */
    private const STEP_MINUTES = 15;
    private const DAY_START    = 8 * 60;
    private const DAY_END      = 24 * 60;

    /** [date, project id, minutes, description] — durations snapped to quarter hours. */
    private const ROWS = [
        ['2026-07-14', 44,  60, 'Programmierung'],
        ['2026-07-17', 44, 210, 'Programmierung'],
        ['2026-07-18', 44, 240, 'Programmierung'],
        ['2026-07-20', 44, 270, 'Programmierung'],
        ['2026-07-23', 44,  30, 'Programmierung'],
        ['2026-07-24', 35,  30, 'Anpassungen'],
        ['2026-07-24', 45, 150, '- staging.hobel.ch aufgesetzt (Kopie von hobel.ch), - testshop.hobel.ch wieder aktiviert Sticky Fehlermeldung im Frontend Excel Export Produkte Sort. innerhalb Kategorie Start-Bild Auswahl auf Ebene Produkt'],
        ['2026-07-25', 38,  90, 'Vimeo Einbindung, Testumgebung'],
        ['2026-07-25', 45, 240, 'Umsetzung 2-Achsen Variationen, Anpassungen Export'],
        ['2026-07-28', 44,  30, 'Korrekturen, GoLive'],
        ['2026-08-04', 41,  30, 'Neue Dozentin, Support'],
        ['2026-08-05', 41,  30, 'Support Bearbeitung Dozentenprofile'],
        ['2026-08-05', 46,  15, 'Integration Google Tag Snippet'],
        ['2026-08-05', 38,  30, 'Integration Sound Autoplay'],
        ['2026-08-06', 41,  75, 'Support User mit 2 verschiedenen Rollen'],
        ['2026-08-06', 34,  15, 'Analyse betreffend Integration von mehreren Stellenausschreibungen'],
        ['2026-08-10', 47,  30, 'Anpassungen Kontakt'],
        ['2026-08-11', 44,  30, 'Anpassungen und Live-Schaltung'],
        ['2026-08-11', 34,  30, 'Integration Jobseite, Verknüpfung Footer'],
        ['2026-08-12', 46,  90, 'Erweiterungen Formular mit Info-Text «Separates Formular», Korrekturen'],
        ['2026-08-13', 48,  30, 'Fix für Safari Bug (weisse Linie); Sitemap erstellt und über Google Search Konsole registriert'],
        ['2026-08-13', 50,  15, 'Anpassungen Navigation (Bug in EN)'],
        ['2026-08-13', 41,  45, '2 neue Stellenausschreibungen, Support Benutzer'],
        ['2026-08-14', 41,  15, 'Anpassung Liste Stelleninserate'],
        ['2026-08-14', 49,  60, 'Umstellung Mailversand (ohne SSL)'],
        ['2026-08-17', 49,  60, 'gatra.ch – HTML-Code bereinigt'],
        ['2026-08-17', 49,  75, 'guttrans.ch – HTML-Code bereinigt'],
        ['2026-08-17', 41,  15, 'PDF «IWF-anerkannte Fortbildungsveranstaltungen / 2026» angepasst'],
        ['2026-08-17', 35,  90, 'Verknüpfung mit Melon, Anpassungen Frontend, Backend für Verwaltung der Gewerbeflächen'],
        ['2026-08-18', 50,  75, 'Diverse Anpassungen, Übersetzungen EN'],
        ['2026-08-18', 34,  30, 'Anpassung News Message Startseite (Formatierung erlauben)'],
        ['2026-08-18', 39,  60, 'Anpassung Author:innen Rolle: Editieren/Löschen von Portraits; Format- und Grössenangaben bei Projekt Bildern'],
        ['2026-08-20', 50,  15, 'Korrekturen (Übersetzungen)'],
        ['2026-08-20', 35,  60, 'GoLive'],
        ['2026-08-21', 52,  15, 'Anpassung Text'],
        ['2026-08-21', 41,  15, 'E-Mail Support Frau Barwinski'],
        ['2026-08-23', 46,  60, 'Aufbereiten „Geschützte Seiten 2027“, Downloadschutz verlinkte Dateien'],
        ['2026-08-24', 41,  15, 'Deaktivieren Jubiläumsseite'],
        ['2026-08-24', 53,  15, 'Support zur unterschiedlichen Darstellung des «x» durch die Schriftart'],
        ['2026-08-24', 41,  75, 'Versandliste auf Basis der Anmeldungen zum Jubiläumsanlass erstellt. Footer-Text in die DB-Mailingliste integriert.'],
        ['2026-08-25', 46,  45, 'Geschützte Seiten für 2027 aufbereitet mit eigenem User aufgesetzt. Neu Logik, Redirects by user. User hardcodiert im config/blindside'],
        ['2026-08-28', 41,  15, 'Erstellen Admin-User (Lea Ruckstuhl)'],
        ['2026-08-30', 41,  15, 'Support: grosse Download Datei für Mailing'],
        ['2026-08-30', 41,  15, 'Anpassung bei Dozentin'],
        ['2026-08-31', 54,  90, 'Live Schaltung, Umschreiben Cookie Banner'],
        ['2026-09-01', 53,  30, 'Option zur Integration der Preisliste inkl. Blindlink'],
        ['2026-09-01', 34,  30, 'Aufschalten Gesuchs-Formular'],
    ];

    public function handle()
    {
        $rows = $this->rows();

        if ($missing = $this->missingProjects($rows)) {
            $this->error('Unknown project ids: ' . implode(', ', $missing));

            return self::FAILURE;
        }

        $planned = $this->plan($rows);
        $this->render($planned);

        $new = array_filter($planned, fn ($row) => !$row['duplicate']);

        if (!$new) {
            $this->info('Nothing to import — every row is already present.');

            return self::SUCCESS;
        }

        if ($this->option('dry-run')) {
            $this->info(count($new) . ' entries would be created. Nothing was written.');

            return self::SUCCESS;
        }

        if (!$this->option('force') && !$this->confirm('Create ' . count($new) . ' time entries?')) {
            return self::SUCCESS;
        }

        DB::transaction(function () use ($new) {
            foreach ($new as $row) {
                TimeEntry::create([
                    'project_id'  => $row['project_id'],
                    'is_billable' => true,
                    'date'        => $row['date'],
                    'time_from'   => $row['time_from'],
                    'time_to'     => $row['time_to'],
                    'hours'       => round($row['minutes'] / 60, 2),
                    'description' => $row['description'],
                ]);
            }
        });

        $this->info('Imported ' . count($new) . ' time entries.');

        return self::SUCCESS;
    }

    /** Normalizes the embedded tuples into the shape the planner works with. */
    private function rows(): array
    {
        return array_map(fn (array $row) => [
            'date'        => $row[0],
            'project_id'  => $row[1],
            'minutes'     => $row[2],
            'description' => $row[3],
        ], self::ROWS);
    }

    private function missingProjects(array $rows): array
    {
        $ids     = array_values(array_unique(array_column($rows, 'project_id')));
        $present = Project::whereIn('id', $ids)->pluck('id')->all();

        return array_values(array_diff($ids, $present));
    }

    /**
     * Assigns each row a time span. Rows are placed in sheet order per day, each into
     * the earliest free slot at or after 08:00 that fits its full duration.
     */
    private function plan(array $rows): array
    {
        $byDate = [];
        foreach ($rows as $row) {
            $byDate[$row['date']][] = $row;
        }
        ksort($byDate);

        $planned = [];

        foreach ($byDate as $date => $dayRows) {
            $busy     = $this->bookedSpans($date);
            $existing = $this->existingDescriptions($date);

            foreach ($dayRows as $row) {
                // A row already imported keeps its original slot rather than claiming a new one.
                $key = $row['project_id'] . '|' . $row['description'];
                if (in_array($key, $existing, true)) {
                    $planned[] = $row + ['time_from' => null, 'time_to' => null, 'duplicate' => true];
                    continue;
                }

                $start = $this->firstFreeSlot($busy, $row['minutes']);

                if ($start === null) {
                    $this->warn("No free slot on {$date} for {$row['minutes']} min — skipped.");
                    continue;
                }

                $end    = $start + $row['minutes'];
                $busy[] = [$start, $end];
                usort($busy, fn ($a, $b) => $a[0] <=> $b[0]);

                $planned[] = $row + [
                    'time_from' => $this->format($start),
                    'time_to'   => $this->format($end),
                    'duplicate' => false,
                ];
            }
        }

        return $planned;
    }

    /** Spans already booked on a date, ordered by start. Entries without times can't block anything. */
    private function bookedSpans(string $date): array
    {
        $spans = TimeEntry::whereDate('date', $date)
            ->whereNotNull('time_from')
            ->whereNotNull('time_to')
            ->get()
            ->map(fn (TimeEntry $entry) => [$this->minutes($entry->time_from), $this->minutes($entry->time_to)])
            ->all();

        usort($spans, fn ($a, $b) => $a[0] <=> $b[0]);

        return $spans;
    }

    private function existingDescriptions(string $date): array
    {
        return TimeEntry::whereDate('date', $date)
            ->get()
            ->map(fn (TimeEntry $entry) => $entry->project_id . '|' . $entry->description)
            ->all();
    }

    /** Walks the booked spans and returns the first start that leaves room for $minutes. */
    private function firstFreeSlot(array $busy, int $minutes): ?int
    {
        $candidate = self::DAY_START;

        foreach ($busy as [$start, $end]) {
            if ($end <= $candidate) {
                continue;
            }
            if ($candidate + $minutes <= $start) {
                return $candidate;
            }
            $candidate = (int) ceil($end / self::STEP_MINUTES) * self::STEP_MINUTES;
        }

        return $candidate + $minutes <= self::DAY_END ? $candidate : null;
    }

    private function render(array $planned): void
    {
        $this->table(
            ['Date', 'From', 'To', 'H', 'Project', 'Description'],
            array_map(function (array $row) {
                return [
                    $row['date'],
                    $row['duplicate'] ? '—' : $row['time_from'],
                    $row['duplicate'] ? 'exists' : $row['time_to'],
                    number_format($row['minutes'] / 60, 2),
                    $row['project_id'],
                    mb_strimwidth($row['description'], 0, 60, '…'),
                ];
            }, $planned)
        );

        $minutes = array_sum(array_column(array_filter($planned, fn ($row) => !$row['duplicate']), 'minutes'));
        $this->line(sprintf('Total: %s hours across %d rows.', number_format($minutes / 60, 2), count($planned)));
    }

    private function minutes(string $time): int
    {
        [$hours, $minutes] = array_pad(explode(':', $time), 2, 0);

        return ((int) $hours) * 60 + (int) $minutes;
    }

    private function format(int $minutes): string
    {
        return sprintf('%02d:%02d', intdiv($minutes, 60), $minutes % 60);
    }
}
