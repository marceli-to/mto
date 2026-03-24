<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('quote_sections', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('quote_id');
            $table->string('title');
            $table->string('total_label')->default('Total');
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('quote_id')->references('id')->on('quotes')->cascadeOnDelete();
        });
    }

    public function down()
    {
        Schema::dropIfExists('quote_sections');
    }
};
