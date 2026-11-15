<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('ccm_events', function (Blueprint $table) {
            $table->boolean('flights_enabled')->default(false)->after('status');
        });
    }

    public function down()
    {
        Schema::table('ccm_events', function (Blueprint $table) {
            $table->dropColumn('flights_enabled');
        });
    }
};