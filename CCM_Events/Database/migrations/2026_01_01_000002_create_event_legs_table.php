<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ccm_event_legs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('ccm_events')->cascadeOnDelete();
            $table->unsignedInteger('leg_number');
            $table->string('departure_icao', 4);
            $table->string('arrival_icao', 4);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ccm_event_legs');
    }
};