<?php

declare(strict_types=1);

return [

    /*
    | The fagus binary (FAGUS_BINARY). Without a path, it is the one the package installed (with
    | Composer or `php artisan fagus:install`), else the one on the PATH.
    */
    'binary' => env('FAGUS_BINARY'),

    /*
    | Directories with fonts for every document: TTF, OTF, TTC, OTC, WOFF and WOFF2 files, also
    | in subdirectories. Directories that do not exist are skipped. Documents can bring their own
    | fonts with @font-face too.
    */
    'fonts' => [
        resource_path('fonts'),
    ],

    /*
    | Whether the fonts installed on the system serve too, as in Chrome: for the families that
    | documents name, the generic families and characters that other fonts lack. Off by default,
    | so that documents look the same on every machine.
    */
    'system_fonts' => env('FAGUS_SYSTEM_FONTS', false),

    /*
    | The language of the texts form controls show of their own (the labels of submit buttons,
    | the placeholders and formats of date fields …) where a document gives no lang, e.g. "de-DE".
    | Chrome writes them in the language of its user interface; American English if null.
    */
    'locale' => env('FAGUS_LOCALE'),

    /*
    | The families used for the generic families sans-serif, serif and monospace.
    */
    'families' => [
        'sans-serif' => null,
        'serif' => null,
        'monospace' => null,
    ],

    /*
    | The directory relative URLs in documents (stylesheets, images, fonts) resolve against. Only
    | files inside it, or inside `root` if set, are read.
    */
    'base_directory' => public_path(),

    'root' => null,

    /*
    | Whether PDFs are tagged: with the logical structure of the document, which screen readers
    | read. Single documents can say otherwise with tagged() or ask for PDF/UA with accessible().
    */
    'tagged' => true,

    /*
    | Seconds to wait for each answer of the worker before it is stopped. Not on Windows, where
    | PHP cannot wait for a pipe with a time limit.
    */
    'timeout' => 60,

];
