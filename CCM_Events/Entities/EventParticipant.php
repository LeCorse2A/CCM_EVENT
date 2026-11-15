<?php

namespace Modules\CCM_Events\Entities;

use Illuminate\Database\Eloquent\Model;

class EventParticipant extends Model
{
    protected $table = 'ccm_event_participants';

    protected $fillable = [
        'event_id',
        'leg_id',
        'user_id',
        'status',
    ];

    public function event()
    {
        return $this->belongsTo(Event::class, 'event_id');
    }

    public function leg()
    {
        return $this->belongsTo(EventLeg::class, 'leg_id');
    }

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }
}