<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ccm_events', function (Blueprint $table) {
            // 'flight' = évènement vol (aéroport, stand, avion, legs)
            // 'non_flight' = évènement hors vol (ex. soirée cinéma)
            // Les évènements existants restent des vols (valeur par défaut).
            $table->string('type', 20)->default('flight')->after('category');
        });
    }

    public function down(): void
    {
        Schema::table('ccm_events', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
