<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('time_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->nullable()->constrained()->cascadeOnDelete(); // null for activity entries
            $table->string('activity')->nullable();      // set for activity entries (admin/gym/…); mutually exclusive with project_id
            $table->boolean('is_billable')->default(true);
            $table->date('date');
            $table->decimal('hours', 6, 2);              // e.g. 1.25
            $table->text('description')->nullable();
            $table->decimal('rate', 10, 2)->nullable();  // per-entry override; null = inherit project rate
            $table->foreignId('invoice_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('invoice_position_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['project_id', 'date', 'id']); // supports oldest-first cumulative ordering
            $table->index('invoice_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('time_entries');
    }
};
