<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ccm_event_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('ccm_events')->cascadeOnDelete();
            $table->foreignId('leg_id')->nullable()->constrained('ccm_event_legs')->cascadeOnDelete();
            $table->unsignedBigInteger('user_id');
            $table->enum('status', ['participating', 'not_participating'])->default('participating');
            $table->timestamps();

            // Un pilote ne peut participer/décliner qu'une fois par event (leg_id NULL)
            // ou qu'une fois par leg précis (leg_id rempli)
            $table->unique(['event_id', 'leg_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ccm_event_participants');
    }
};