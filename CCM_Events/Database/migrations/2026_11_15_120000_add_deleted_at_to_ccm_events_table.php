<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Suppression douce des évènements (SoftDeletes) — ajoutée seulement si absente
    public function up(): void
    {
        if (!Schema::hasColumn('ccm_events', 'deleted_at')) {
            Schema::table('ccm_events', function (Blueprint $table) {
                $table->softDeletes();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('ccm_events', 'deleted_at')) {
            Schema::table('ccm_events', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }
    }
};
