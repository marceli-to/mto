<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rates', function (Blueprint $table) {
            // The rate a new project starts on. Exactly one row carries the flag.
            $table->boolean('is_default')->default(false)->after('amount');
        });

        DB::table('rates')->where('description', 'Standard')->update(['is_default' => true]);
    }

    public function down(): void
    {
        Schema::table('rates', function (Blueprint $table) {
            $table->dropColumn('is_default');
        });
    }
};
