<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('quote_positions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('quote_section_id');
            $table->text('description');
            $table->decimal('amount', 10, 2)->default(0.00);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('quote_section_id')->references('id')->on('quote_sections')->cascadeOnDelete();
        });
    }

    public function down()
    {
        Schema::dropIfExists('quote_positions');
    }
};
