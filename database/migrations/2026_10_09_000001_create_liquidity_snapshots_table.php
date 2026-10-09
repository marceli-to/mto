<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('liquidity_snapshots', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->json('data'); // balance, manual items, picked invoice + project ids
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('liquidity_snapshots');
    }
};
