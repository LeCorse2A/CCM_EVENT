<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ccm_event_reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('ccm_events')->cascadeOnDelete();
            $table->unsignedBigInteger('user_id');
            $table->string('airport_icao', 4);
            $table->string('stand')->nullable();
            $table->unsignedBigInteger('aircraft_id')->nullable();
            $table->unsignedBigInteger('original_airport_id')->nullable();
            $table->boolean('flight_active')->default(false); // remplace "special_flight_active", activé par le responsable
            $table->timestamps();

            // Un seul pilote par stand pour un même event (unicité validée plus tôt)
            $table->unique(['event_id', 'airport_icao', 'stand']);
            // Un pilote ne réserve qu'une fois par event
            $table->unique(['event_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ccm_event_reservations');
    }
};