<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ccm_events', function (Blueprint $table) {
            // adapte le nom de la table si ce n'est pas "ccm_events"
            $table->string('banner_path')->nullable()->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('ccm_events', function (Blueprint $table) {
            $table->dropColumn('banner_path');
        });
    }
};