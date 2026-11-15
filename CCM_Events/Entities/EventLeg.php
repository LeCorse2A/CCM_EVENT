<?php

namespace Modules\CCM_Events\Entities;

use Illuminate\Database\Eloquent\Model;

class EventLeg extends Model
{
    protected $table = 'ccm_event_legs';

    protected $fillable = [
        'event_id',
        'leg_number',
        'departure_icao',
        'arrival_icao',
    ];

    public function event()
    {
        return $this->belongsTo(Event::class, 'event_id');
    }

    public function participants()
    {
        return $this->hasMany(EventParticipant::class, 'leg_id');
    }
}