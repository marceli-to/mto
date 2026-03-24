<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('quotes', function (Blueprint $table) {
            $table->id();
            $table->string('number')->default('00.0000');
            $table->string('title');
            $table->date('date');
            $table->unsignedBigInteger('client_id');
            $table->string('status')->default('draft');
            $table->string('intro_greeting')->nullable();
            $table->text('intro_text')->nullable();
            $table->decimal('vat_rate', 4, 2)->default(8.10);
            $table->decimal('daily_rate', 8, 2)->nullable();
            $table->decimal('hourly_rate', 8, 2)->nullable();
            $table->boolean('include_terms_page')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('client_id')->references('id')->on('clients');
        });
    }

    public function down()
    {
        Schema::dropIfExists('quotes');
    }
};
