<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterInvoicePositionsTableAddIsFlatAmount extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('invoice_positions', function (Blueprint $table) {
            $table->decimal('amount', 8,2)->default(0.00)->after('hours');
            $table->integer('is_flat')->default(0)->after('hours');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('invoice_positions', function (Blueprint $table) {
            $table->dropColumn('amount');
            $table->dropColumn('is_flat');
        });
    }
}
