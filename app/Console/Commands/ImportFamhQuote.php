<?php

namespace App\Console\Commands;

use App\Actions\Quote\SyncContent;
use App\Models\Client;
use App\Models\Quote;
use Illuminate\Console\Command;

/**
 * One-off: brings the FAMH quote (built locally from the former InDesign
 * document) into production. Remove once it has run.
 */
class ImportFamhQuote extends Command
{
    protected $signature = 'quote:import-famh {--client= : Client id, defaults to the client with acronym FUH}';

    protected $description = 'Create the "Relaunch FAMH-Webseite" quote (one-off)';

    public function handle(): int
    {
        $client = $this->option('client')
            ? Client::find($this->option('client'))
            : Client::where('acronym', 'FUH')->first();

        if (!$client) {
            $this->error('Client not found. Pass --client=<id>.');
            return self::FAILURE;
        }

        $data = $this->data();

        if (Quote::where('client_id', $client->id)->where('title', $data['title'])->exists()) {
            $this->error("{$client->name} already has a quote \"{$data['title']}\".");
            return self::FAILURE;
        }

        if (!$this->confirm("Create the quote for {$client->name} (id {$client->id})?", true)) {
            return self::SUCCESS;
        }

        $quote = new Quote([
            'label' => $data['label'],
            'title' => $data['title'],
            'date' => $data['date'],
            'client_id' => $client->id,
            'vat_rate' => $data['vat_rate'],
            'intro_greeting' => $data['intro_greeting'],
            'intro_text' => $data['intro_text'],
        ]);
        $quote->status = Quote::DRAFT;
        $quote->save();

        (new SyncContent)->execute($quote, $data['pages'], $data['sections']);
        $quote->generateNumber();

        $this->info("Created quote {$quote->number} (id {$quote->id}).");

        return self::SUCCESS;
    }

    private function data(): array
    {
        return [
          'label' => 'Offerte',
          'title' => 'Relaunch FAMH-Webseite',
          'date' => '2026-10-30',
          'vat_rate' => 8.1,
          'intro_greeting' => 'Hallo Dani',
          'intro_text' => '<p>Vielen Dank für die Einladung zum Pitch.</p><p>Basierend auf unserem Gespräch, dem Austausch mit der FAMH sowie dem Briefing vom 1. Oktober und den Mockups vom 30. September kann ich die Umsetzung der Webseite wie nachfolgend beschrieben offerieren.</p><p>Ich würde mich sehr freuen, an diesem Projekt mitzuwirken.</p><p>Lieber Gruss</p><p>Marcel Stadelmann</p>',
          'pages' => [
            [
              'type' => 'text',
              'content' => '<h2>Ausgangslage und Verständnis</h2><p>Die FAMH braucht zwei Dinge in einem System: eine moderne, zweisprachige Verbandswebseite und eine Reihe von Fachfunktionen für Mitglieder, Titelträger:innen und das Generalsekretariat.</p><p>Die Webseite vermittelt Positionen, Qualitätsstandards und den Weg zum FAMH-Titel verständlich für Behörden, Fachpersonen und Öffentlichkeit. Die Fachfunktionen umfassen durchsuchbare Verzeichnisse, die Stellenbörse, den Mitgliederbereich mit Datenaustausch und GV-Abstimmungen sowie die Anbindung an ManagePlus (M+).</p><p>Das Generalsekretariat soll alles mit minimalem technischem Vorwissen verwalten können und die Lösung soll erweiterbar bleiben, wenn beispielsweise weitere Schnittstellen hinzukommen oder ein externes Abstimmungstool zum Einsatz kommt.</p><h2>Lösungsansatz</h2><p>Ich setze Statamic ein, ein modernes CMS auf Basis des PHP-Frameworks Laravel. Damit erhält die Geschäftsstelle ein intuitives System und die verschiedenen Module entstehen als sauberer, testbarer Code statt als Kombination von Plugins.</p><p>Die Lösung hat drei Schichten:</p><h3>1. Redaktionelle Inhalte in Statamic</h3><p>Aktuelles, Stellungnahmen, Positionen, Laborbericht, FAQ, Gesetze, Organisation und alle Informationsseiten. Mehrsprachig Deutsch und Französisch, mit Rollen für Administration und Redaktion.</p><h3>2. Daten und Fachmodule in Laravel</h3><p>Labore gemäss Qualitätsvertrag, Weiterbildungsstätten, Positivliste, Mitgliederverzeichnis, Kurse, Stellenbörse, Datenaustausch und Abstimmungen (optional). Diese Daten liegen in einer Datenbank, werden über eigene Module importiert und abgeglichen und sind im selben Statamic-Backend pflegbar.</p><h3>3. Anbindung an ManagePlus</h3><p>Anmeldung der Mitglieder über die M+-API mit Rollen (Vorstand, Fachausschuss, Mitglied, Generalsekretariat), laufender Abgleich von Mitgliederverzeichnis und Weiterbildungsstätten, Übergabe von Aufnahmegesuchen an M+.</p>',
            ],
            [
              'type' => 'text',
              'content' => '<h2>Grundleistungen</h2><p>Die Grundleistungen decken die gesamte Informationsarchitektur des Briefings ab, inklusive Login und laufendem Datenabgleich mit ManagePlus:</p><h3>1. Projektleitung</h3><p>Marcel Stadelmann als zentrale Ansprechperson (SPOC) für FAMH und furrerhugi, regelmässige Abstimmung, Koordination mit M+</p><h3>2. Konzept und Spezifikation</h3><p>Technisches Konzept, Content-Modell, Abklärungen der Schnittstellen</p><h3>3. Setup und Infrastruktur</h3><p>Statamic Pro, Mehrsprachigkeit DE/FR, Staging- und Live-Umgebung, automatisiertes Deployment</p><h3>4. Frontend</h3><p>Umsetzung des Screendesigns als responsive Komponenten für Desktop, Tablet und Smartphone</p><h3>5. Home, Aktuelles, Positionen &amp; Themen</h3><p>News mit Kategorien und Filter, Stellungnahmen und Vernehmlassungen mit PDF je Sprache, fachliche Positionen nach Disziplin, digitaler Laborbericht mit Archiv</p><h3>6. Qualität &amp; Standards</h3><p>Informationsseiten, Qualitätsvertrag mit durchsuchbarer Laborliste, Detailansicht und jährlichem Import mit Prüfung und Vorschau</p><h3>7. Beruf &amp; Bildung</h3><p>Weiterbildung mit Prozessdarstellung und Unterseiten, Liste der Weiterbildungsstätten mit laufendem Abgleich aus M+, Fortbildungskurse mit Archiv und Positivliste (jeweils Pflege im Backend oder Import), Gleichwertigkeit</p><h3>8. Stellenbörse</h3><p>Angebote und Gesuche mit Filter, Eingabeformular mit Angabe der Mitgliedschaft und Rechnungsadresse für Nichtmitglieder, Moderation durch das Generalsekretariat, Benachrichtigung, automatisches Ablaufen</p><h3>9. Verband &amp; Mitglieder</h3><p>Mitgliedschaft mit Kategorien, Aufnahmegesuch als Online-Formular mit direkter Übergabe an M+, Mitgliederverzeichnis mit laufendem Abgleich aus M+, GV-Daten zum Eintragen in den Kalender, Organisation mit Gremien</p><h3>10. Mitgliederbereich</h3><p>Login über die M+-API, Rollen und Rechte, Übersicht, interne News</p><h3>11. Datenaustausch</h3><p>Geschützte Dokumente mit Sichtbarkeit pro Gremium, Rubriken, E-Mail-Benachrichtigung</p><h3>12. Labormedizin, FAQ, Allgemeines</h3><p>Labormedizin mit fünf Disziplinen, FAQ nach Themen, Suche DE/FR, Newsletter-Anmeldung (inkl. Laborbericht), Standorte der Mitglieder als Link auf das Mitgliederverzeichnis, Impressum, Datenschutz</p><h3>13. SEO, Performance und Barrierefreiheit</h3><p>Onsite-SEO (Metadaten pro Seite und Sprache, Sitemap, strukturierte Daten, Weiterleitungen der alten URLs), optimierte Bilder und Caching für kurze Ladezeiten, Umsetzung und Prüfung nach WCAG 2.2 AA (ohne externes Audit)</p><h3>14. Content-Migration</h3><p>Übernahme der gelieferten Inhalte in Deutsch und Französisch</p><h3>15. Testing, Abnahme, Schulung</h3><p>Tests auf den Browsern und Geräten gemäss Briefing, Abnahme, Schulung der Redaktion, Dokumentation</p>',
            ],
            [
              'type' => 'text',
              'content' => '<h2>Optionale Leistungen</h2><p>Die optionalen Leistungen lassen sich auch nach Projektstart einzeln beauftragen.</p><h3>1. Abstimmungsmodul GV</h3><p>Traktanden und Wahlen erfassen, Stimmabgabe im Mitgliederbereich, Live-Auswertung, Export Excel/CSV, Erinnerungen, Archiv</p><h3>2. M+-API für Fortbildungskurse (sobald verfügbar)</h3><p>Kursliste wird automatisch aus M+ synchronisiert, Kurse werden über ein Formular direkt an M+ übergeben. Voraussetzung ist die Fortbildungskontrolle in M+; den Fixpreis offeriere ich, sobald deren Umsetzung feststeht.</p><h3>3. Standortkarte Mitglieder</h3><p>Interaktive Karte der Mitglieder mit Filter</p><h3>4. PDF-Volltextsuche</h3><p>Die Suche findet auch Inhalte in Stellungnahmen, Reglementen und weiteren PDFs</p><h3>5. Weitere Sprache</h3><p>Einrichten einer weiteren Sprache und Übernahme der gelieferten Inhalte (z. B. Italienisch).</p>',
            ],
            [
              'type' => 'prices',
              'content' => '<h2>Preise</h2><p>Alle Positionen sind Fixpreise. Nicht enthalten sind Hosting, Übersetzungen und allfällige Kosten auf Seite von ManagePlus.</p>',
            ],
            [
              'type' => 'text',
              'content' => '<h2>Annahmen und Abgrenzungen</h2><p>Der Fixpreis gilt unter folgenden Annahmen. Weichen sie ab, bespreche ich die Auswirkungen, bevor Mehraufwand entsteht.</p><h3>ManagePlus</h3><ul><li><p>M+ stellt eine Testumgebung und Testzugänge für alle Rollen bereit.</p></li><li><p>Jedes Mitglied hat einen Account in ManagePlus. Accounts werden in M+ eröffnet, Passwörter dort zurückgesetzt.</p></li><li><p>Rollen und Mitgliederkategorien (Vorstand, Fachausschuss, Mitglied, Generalsekretariat) sowie die Daten der Mitglieder und Weiterbildungsstätten sind im Modul Personen/Institutionen abgebildet und für die Website lesbar.</p></li><li><p>Die FAMH legt die Berechtigungen des technischen API-Benutzers so fest, dass die vereinbarten Abgleiche und die Übergabe von Aufnahmegesuchen möglich sind.</p></li><li><p>Wo kein direkter Abgleich vorgesehen ist, exportiert M+ die Daten als CSV oder Excel.</p></li><li><p>Anpassungen am System von M+ sind nicht Teil dieser Offerte.</p></li></ul><h3>Inhalte und Design</h3><ul><li><p>furrerhugi liefert das Screendesign in Figma für Desktop, Tablet und Smartphone, inklusive aller Zustände der Komponenten.</p></li><li><p>Die FAMH liefert die Inhalte in Deutsch und Französisch final und rechtzeitig für die Migration. Übersetzungen sind nicht enthalten.</p></li></ul>',
            ],
            [
              'type' => 'text',
              'content' => '<h2>Sicherheit und Datenschutz</h2><p>Folgende Massnahmen sind in den Grundleistungen enthalten:</p><h3>Verschlüsselte Verbindung</h3><p>Die gesamte Webseite läuft über HTTPS mit aktuellen Sicherheits-Headern.</p><h3>Keine Passwörter auf der Webseite</h3><p>Mitglieder melden sich über ManagePlus an. Die Webseite prüft die Zugangsdaten über die M+-API und speichert selbst keine Passwörter. Wiederholte Fehlversuche beim Login werden geblockt.</p><h3>Rollen und Rechte</h3><p>Im CMS und im Mitgliederbereich sieht jede Person nur, was sie für ihre Rolle braucht. Geschützte Dokumente sind nur für die berechtigten Gremien zugänglich.</p><h3>Formulare</h3><p>Formulare wie Stelleninserate oder Aufnahmegesuche sind vor Spam und Missbrauch geschützt.</p><h3>Datenschutz gemäss revDSG</h3><p>Hosting in der Schweiz wird empfohlen, keine Tracker ohne Einwilligung, Schriften werden lokal ausgeliefert, externe Dienste nur wo nötig. Auf Wunsch richte ich eine datenschutzfreundliche Webanalyse ein (z. B. Matomo oder Plausible).</p>',
            ],
            [
              'type' => 'text',
              'content' => '<h2>Ansprechperson</h2><p>Ich bin während des gesamten Projekts die zentrale Ansprechperson für FAMH, furrerhugi und ManagePlus, von der Konzeption über die Umsetzung bis zum Betrieb. Es gibt keine Übergaben zwischen Projektleitung, Entwicklung und Support.</p><h2>Über mich</h2><p>Ich bin selbständiger Entwickler und technischer Berater in Zürich und setze seit über 15 Jahren Webseiten und Webanwendungen mit Laravel und Statamic um, unter anderem für Kulturinstitutionen, KMU und Agenturen. Oft arbeite ich an der Schnittstelle zwischen Kundschaft, Agentur und interner IT. Für die Umsetzung arbeite ich mit KI-Agenten; Konzept, Architektur, Prüfung und die Verantwortung für das Ergebnis liegen bei mir.</p><h2>Erreichbarkeit und Vertretung</h2><p>Ich bin per E-Mail (m@marceli.to) und Telefon (078 749 74 09) erreichbar und antworte in der Regel innerhalb eines Arbeitstages. Bei Abwesenheit vertritt mich mein früherer Geschäftspartner Karsten Zingg (Jamon digital AG).</p>',
            ],
            [
              'type' => 'text',
              'content' => '<h2>Anforderungen Hosting</h2><p>Das Hosting muss folgende Anforderungen erfüllen:</p><ul><li><p>PHP 8.3+ mit den gängigen Erweiterungen, Composer, Git</p></li><li><p>Datenbank MySQL 8+ oder MariaDB 10.6+</p></li><li><p>SSH-Zugang</p></li><li><p>Cronjobs (im Minutentakt)</p></li><li><p>Getrennte Staging- und Live-Umgebung (Subdomains)</p></li><li><p>SSL-Zertifikate</p></li><li><p>Tägliche Backups</p></li></ul><h2>Betrieb und Wartung</h2><p>Nach dem Go-live betreue ich die Webseite nach Aufwand zu CHF 150 pro Stunde, ohne Pauschale.</p><h3>Hosting</h3><p>Einrichtung und Betrieb gemäss den Anforderungen oben</p><h3>Updates und Sicherheit</h3><p>Regelmässige Aktualisierung von Statamic, Laravel und Add-ons sowie Überwachung der Verfügbarkeit</p><h3>Weiterentwicklung</h3><p>Neue Funktionen, etwa zusätzliche M+-Schnittstellen, offeriere ich jeweils als Fixpreis.</p>',
            ],
            [
              'type' => 'text',
              'content' => '<h2>Konditionen</h2><h3>Gültigkeit</h3><p>Diese Offerte ist drei Monate ab Offertdatum gültig.</p><h3>Preise</h3><p>Alle Preise in CHF, exklusive 8.1% MwSt. Grundleistungen und Optionen sind Fixpreise.</p><h3>Zahlungsplan</h3><p>30% bei Auftragserteilung, 40% bei Abnahme des Staging-Systems, 30% nach dem Go-live.</p><h3>Zusätzliche Leistungen</h3><p>Leistungen ausserhalb des offerierten Umfangs verrechne ich nach vorgängiger Absprache nach Aufwand zu CHF 150 pro Stunde.</p><h3>Nutzungsrechte</h3><p>Mit der vollständigen Bezahlung erhält die FAMH das zeitlich und örtlich unbeschränkte Recht, den für dieses Projekt entwickelten Code zu nutzen, anzupassen und durch Dritte weiterentwickeln zu lassen. Statamic, Laravel und weitere eingesetzte Software unterstehen ihren eigenen Lizenzen. Im Übrigen gilt das Bundesgesetz über das Urheberrecht und verwandte Schutzrechte (URG).</p><h3>Anwendbares Recht</h3><p>Es gilt Schweizer Recht.</p>',
            ],
          ],
          'sections' => [
            [
              'title' => 'Grundleistungen',
              'total_label' => 'Total',
              'show_total' => false,
              'show_vat_note' => false,
              'currency' => 'CHF',
              'positions' => [
                [
                  'description' => 'Relaunch FAMH-Webseite gemäss Grundleistungen',
                  'amount' => 24000.0,
                  'amount_text' => NULL,
                ],
              ],
            ],
            [
              'title' => 'Optionen',
              'total_label' => 'Total',
              'show_total' => false,
              'show_vat_note' => true,
              'currency' => 'CHF',
              'positions' => [
                [
                  'description' => 'Abstimmungsmodul GV',
                  'amount' => 3600.0,
                  'amount_text' => NULL,
                ],
                [
                  'description' => 'M+-API für Fortbildungskurse',
                  'amount' => 0.0,
                  'amount_text' => 'Fixpreis folgt',
                ],
                [
                  'description' => 'Standortkarte Mitglieder',
                  'amount' => 1200.0,
                  'amount_text' => NULL,
                ],
                [
                  'description' => 'PDF-Volltextsuche',
                  'amount' => 1800.0,
                  'amount_text' => NULL,
                ],
                [
                  'description' => 'Weitere Sprache (Preis pro Sprache)',
                  'amount' => 2400.0,
                  'amount_text' => NULL,
                ],
              ],
            ],
            [
              'title' => 'Lizenzgebühren',
              'total_label' => 'Total',
              'show_total' => false,
              'show_vat_note' => false,
              'currency' => 'USD',
              'positions' => [
                [
                  'description' => '«Statamic Pro», einmalig inkl. einem Jahr Updates',
                  'amount' => 349.0,
                  'amount_text' => NULL,
                ],
                [
                  'description' => 'Updates ab dem 2. Jahr, jährlich',
                  'amount' => 99.0,
                  'amount_text' => NULL,
                ],
              ],
            ],
          ],
        ];
    }
}