<?php
namespace Modules\CCM_Events\Entities;

use Illuminate\Database\Eloquent\Model;

class EventReservation extends Model
{
    protected $table = 'ccm_event_reservations';

    protected $fillable = [
        'event_id',
        'user_id',
        'airport_icao',
        'stand',
        'aircraft_id',
        'original_airport_id',
        'flight_active',
    ];

    protected $casts = [
        'flight_active' => 'boolean',
    ];

    public function event()
    {
        return $this->belongsTo(Event::class, 'event_id');
    }

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    public function aircraft()
    {
        return $this->belongsTo(\App\Models\Aircraft::class, 'aircraft_id');
    }
}