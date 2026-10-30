<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Quotes become an ordered list of pages: free text pages and one prices
 * page. The hard-coded terms page turns into a text page, and reusable
 * text lives in a small library (quote_blocks).
 */
return new class extends Migration
{
    public function up()
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->string('label')->default('Offerte')->after('number');
        });

        Schema::create('quote_pages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('quote_id');
            $table->string('type')->default('text');
            $table->longText('content')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('quote_id')->references('id')->on('quotes')->cascadeOnDelete();
        });

        Schema::create('quote_blocks', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->longText('content')->nullable();
            $table->boolean('is_default')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::table('quote_sections', function (Blueprint $table) {
            $table->boolean('show_total')->default(true)->after('total_label');
            $table->boolean('show_vat_note')->default(true)->after('show_total');
            $table->string('currency', 3)->default('CHF')->after('show_vat_note');
        });

        Schema::table('quote_positions', function (Blueprint $table) {
            $table->string('amount_text')->nullable()->after('amount');
        });

        // Existing quotes keep their look: "Angebot", prices, then the terms page
        foreach (DB::table('quotes')->get() as $quote) {
            DB::table('quotes')->where('id', $quote->id)->update(['label' => 'Angebot']);

            DB::table('quote_pages')->insert([
                'quote_id' => $quote->id,
                'type' => 'prices',
                'content' => null,
                'sort_order' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if ($quote->include_terms_page) {
                DB::table('quote_pages')->insert([
                    'quote_id' => $quote->id,
                    'type' => 'text',
                    'content' => $this->legacyTerms($quote->daily_rate, $quote->hourly_rate),
                    'sort_order' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        Schema::table('quotes', function (Blueprint $table) {
            $table->dropColumn(['include_terms_page', 'daily_rate', 'hourly_rate']);
        });

        $blocks = [
            ['Konditionen', true, <<<'HTML'
<h2>Konditionen</h2><h3>Gültigkeit</h3><p>Diese Offerte ist drei Monate ab Offertdatum gültig.</p><h3>Preise</h3><p>Alle Preise in CHF, exklusive 8.1% MwSt. Grundleistungen und Optionen sind Fixpreise.</p><h3>Zahlungsplan</h3><p>30% bei Auftragserteilung, 40% bei Abnahme des Staging-Systems, 30% nach dem Go-live.</p><h3>Zusätzliche Leistungen</h3><p>Leistungen ausserhalb des offerierten Umfangs verrechne ich nach vorgängiger Absprache nach Aufwand zu CHF 150 pro Stunde.</p><h3>Nutzungsrechte</h3><p>Mit der vollständigen Bezahlung erhält die Kundschaft das zeitlich und örtlich unbeschränkte Recht, den für dieses Projekt entwickelten Code zu nutzen, anzupassen und durch Dritte weiterentwickeln zu lassen. Statamic, Laravel und weitere eingesetzte Software unterstehen ihren eigenen Lizenzen. Im Übrigen gilt das Bundesgesetz über das Urheberrecht und verwandte Schutzrechte (URG).</p><h3>Anwendbares Recht</h3><p>Es gilt Schweizer Recht.</p>
HTML],
            ['Anforderungen Hosting, Betrieb und Wartung', false, <<<'HTML'
<h2>Anforderungen Hosting</h2><p>Das Hosting muss folgende Anforderungen erfüllen:</p><ul><li><p>PHP 8.3+ mit den gängigen Erweiterungen, Composer, Git</p></li><li><p>Datenbank MySQL 8+ oder MariaDB 10.6+</p></li><li><p>SSH-Zugang</p></li><li><p>Cronjobs (im Minutentakt)</p></li><li><p>Getrennte Staging- und Live-Umgebung (Subdomains)</p></li><li><p>SSL-Zertifikate</p></li><li><p>Tägliche Backups</p></li></ul><h2>Betrieb und Wartung</h2><p>Nach dem Go-live betreue ich die Webseite nach Aufwand zu CHF 150 pro Stunde, ohne Pauschale.</p><h3>Hosting</h3><p>Einrichtung und Betrieb gemäss den Anforderungen oben</p><h3>Updates und Sicherheit</h3><p>Regelmässige Aktualisierung von Statamic, Laravel und Add-ons sowie Überwachung der Verfügbarkeit</p><h3>Weiterentwicklung</h3><p>Neue Funktionen offeriere ich jeweils als Fixpreis.</p>
HTML],
            ['Sicherheit und Datenschutz', false, <<<'HTML'
<h2>Sicherheit und Datenschutz</h2><p>Folgende Massnahmen sind in den Grundleistungen enthalten:</p><h3>Verschlüsselte Verbindung</h3><p>Die gesamte Webseite läuft über HTTPS mit aktuellen Sicherheits-Headern.</p><h3>Rollen und Rechte</h3><p>Im CMS sieht jede Person nur, was sie für ihre Rolle braucht.</p><h3>Formulare</h3><p>Formulare sind vor Spam und Missbrauch geschützt.</p><h3>Datenschutz gemäss revDSG</h3><p>Hosting in der Schweiz wird empfohlen, keine Tracker ohne Einwilligung, Schriften werden lokal ausgeliefert, externe Dienste nur wo nötig. Auf Wunsch richte ich eine datenschutzfreundliche Webanalyse ein (z. B. Matomo oder Plausible).</p>
HTML],
            ['Ansprechperson, Über mich, Erreichbarkeit', false, <<<'HTML'
<h2>Ansprechperson</h2><p>Ich bin während des gesamten Projekts die zentrale Ansprechperson, von der Konzeption über die Umsetzung bis zum Betrieb. Es gibt keine Übergaben zwischen Projektleitung, Entwicklung und Support.</p><h2>Über mich</h2><p>Ich bin selbständiger Entwickler und technischer Berater in Zürich und setze seit über 15 Jahren Webseiten und Webanwendungen mit Laravel und Statamic um, unter anderem für Kulturinstitutionen, KMU und Agenturen. Oft arbeite ich an der Schnittstelle zwischen Kundschaft, Agentur und interner IT. Für die Umsetzung arbeite ich mit KI-Agenten; Konzept, Architektur, Prüfung und die Verantwortung für das Ergebnis liegen bei mir.</p><h2>Erreichbarkeit und Vertretung</h2><p>Ich bin per E-Mail (m@marceli.to) und Telefon (078 749 74 09) erreichbar und antworte in der Regel innerhalb eines Arbeitstages. Bei Abwesenheit vertritt mich mein früherer Geschäftspartner Karsten Zingg (Jamon digital AG).</p>
HTML],
            ['Konditionen (klassisch)', false, $this->legacyTerms(1200, 150)],
        ];

        foreach ($blocks as $i => [$title, $isDefault, $content]) {
            DB::table('quote_blocks')->insert([
                'title' => $title,
                'content' => trim($content),
                'is_default' => $isDefault,
                'sort_order' => $i,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down()
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->decimal('daily_rate', 8, 2)->nullable();
            $table->decimal('hourly_rate', 8, 2)->nullable();
            $table->boolean('include_terms_page')->default(true);
        });

        Schema::table('quotes', function (Blueprint $table) {
            $table->dropColumn('label');
        });

        Schema::table('quote_positions', function (Blueprint $table) {
            $table->dropColumn('amount_text');
        });

        Schema::table('quote_sections', function (Blueprint $table) {
            $table->dropColumn(['show_total', 'show_vat_note', 'currency']);
        });

        Schema::dropIfExists('quote_blocks');
        Schema::dropIfExists('quote_pages');
    }

    /**
     * The text of the former hard-coded terms page.
     */
    private function legacyTerms($dailyRate, $hourlyRate): string
    {
        $rates = '';
        if ($dailyRate) {
            $rates .= '<br>Tagesansatz: Fr. ' . number_format($dailyRate, 2, '.', '');
        }
        if ($hourlyRate) {
            $rates .= '<br>Stundenansatz: Fr. ' . number_format($hourlyRate, 2, '.', '');
        }

        return '<h3>Budget</h3><p>Das Budget basiert auf einer Schätzung des Arbeitsaufwandes. Sofern Änderungen im Projekt eintreten, welche das Budget beeinflussen, wird rechtzeitig auf diesen Umstand aufmerksam gemacht und die Offerte revidiert.' . $rates . '</p>'
            . '<h3>Upgrades</h3><p>Die jährlichen Kosten für Upgrades belaufen sich auf Fr. 150.00. Diese beinhalten die Upgrade-Gebühren (Fr. 60.00) des CMS-Anbieters.</p>'
            . '<h3>Technische Voraussetzungen Webhosting</h3><p>Webserver: Linux/Unix-Umgebung mit Apache, Scriptsprache: PHP 8+, Datenbank: MySQL 5+, Composer und GIT</p>'
            . '<h3>Konditionen</h3><p>Diese Offerte ist 30 Tage gültig. Die Preise verstehen sich exklusiv Mehrwertsteuer.<br>Zahlungsmodus: Nach der Erbringung der in dieser Offerte beschriebenen Leistungen.<br>Nicht in dieser Offerte enthaltene Leistungen werden anhand einer speziellen Vereinbarung verrechnet.</p>'
            . '<h3>Urheberrecht</h3><p>Grundsätzlich gilt das Schweizerische Bundesgesetz über das Urheberrecht und verwandte Schutzrechte (Urheberrechtsgesetze, URG).</p>'
            . '<h3>Zahlung</h3><p>Bei Aufträgen im Wert von über Fr. 10\'000.- gelten folgende Zahlungsbedingungen: 1/3 Anzahlung nach Erhalt der Auftragsbestätigung, 2/3 nach Fertigstellung des Projektes.</p>';
    }
};
