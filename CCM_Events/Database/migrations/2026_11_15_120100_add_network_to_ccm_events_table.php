<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Réseau(x) de l'évènement : "ivao", "vatsim" ou "ivao,vatsim" — ajoutée seulement si absente
    public function up(): void
    {
        if (!Schema::hasColumn('ccm_events', 'network')) {
            Schema::table('ccm_events', function (Blueprint $table) {
                $table->string('network', 20)->nullable()->after('type');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('ccm_events', 'network')) {
            Schema::table('ccm_events', function (Blueprint $table) {
                $table->dropColumn('network');
            });
        }
    }
};
