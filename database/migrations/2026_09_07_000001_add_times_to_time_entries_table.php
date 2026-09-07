<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('time_entries', function (Blueprint $table) {
            // Entries are recorded as a span; `hours` is derived from it on save.
            // Nullable so rows created before the switch keep their hours.
            $table->time('time_from')->nullable()->after('date');
            $table->time('time_to')->nullable()->after('time_from');
        });
    }

    public function down(): void
    {
        Schema::table('time_entries', function (Blueprint $table) {
            $table->dropColumn(['time_from', 'time_to']);
        });
    }
};
