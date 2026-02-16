<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterInvoicesTableAddReminderFields extends Migration
{
    public function up()
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->boolean('is_reminder')->default(false)->after('has_rate_increase_notice');
            $table->tinyInteger('reminder_level')->nullable()->after('is_reminder');
        });
    }

    public function down()
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['is_reminder', 'reminder_level']);
        });
    }
}
