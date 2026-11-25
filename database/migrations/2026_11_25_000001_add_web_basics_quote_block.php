<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Adds a text block that lists what every website gets by default:
 * search engine basics, performance and accessibility.
 */
return new class extends Migration
{
    private const TITLE = 'Suchmaschinen, Performance und Barrierefreiheit';

    public function up()
    {
        $content = <<<'HTML'
<h2>Suchmaschinen, Performance und Barrierefreiheit</h2><p>Folgende Grundlagen sind in jeder Webseite enthalten:</p><h3>Sitemap</h3><p>Eine XML-Sitemap wird automatisch aus den Inhalten erstellt und bei jeder Änderung aktualisiert, damit Suchmaschinen alle Seiten finden.</p><h3>robots.txt</h3><p>Die robots.txt legt fest, welche Bereiche Suchmaschinen erfassen dürfen. Staging-Umgebungen sind für Suchmaschinen gesperrt.</p><h3>llms.txt</h3><p>Eine llms.txt fasst die wichtigsten Inhalte der Webseite für KI-Assistenten wie ChatGPT oder Claude zusammen, damit diese die Webseite korrekt wiedergeben.</p><h3>Bilder und Caching</h3><p>Bilder werden automatisch in passender Grösse und in modernen Formaten (WebP, AVIF) ausgeliefert und erst geladen, wenn sie sichtbar werden. Seiten und Dateien werden zwischengespeichert, damit die Webseite schnell lädt.</p><h3>Barrierefreiheit</h3><p>Semantisches HTML, ARIA-Attribute, Alternativtexte für Bilder, Bedienbarkeit per Tastatur und sichtbare Fokuszustände als Grundlage für eine zugängliche Webseite.</p><h3>Suchmaschinenoptimierung (On-Page)</h3><p>Seitentitel und Meta-Beschreibungen sind pro Seite im CMS pflegbar, dazu sprechende URLs, eine saubere Überschriftenstruktur, Canonical-Links, Open-Graph-Angaben für Vorschauen in sozialen Medien und strukturierte Daten (Schema.org).</p>
HTML;

        DB::table('quote_blocks')->insert([
            'title' => self::TITLE,
            'content' => trim($content),
            'is_default' => false,
            'sort_order' => (int) DB::table('quote_blocks')->max('sort_order') + 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down()
    {
        DB::table('quote_blocks')->where('title', self::TITLE)->delete();
    }
};
