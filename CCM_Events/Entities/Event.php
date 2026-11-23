<?php
namespace Modules\CCM_Events\Entities;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Event extends Model
{
    // Suppression douce : l'évènement disparaît du calendrier et de la page publique,
    // mais reste en base (participants, déclinés, legs) pour l'onglet Statistiques.
    use SoftDeletes;

    public const TYPE_FLIGHT     = 'flight';
    public const TYPE_NON_FLIGHT = 'non_flight';

    protected $table = 'ccm_events';
    protected $fillable = [
        'title',
        'date',
        'start_time',
        'end_time',
        'description',
        'category',
        'type',
        'network',
        'status',
        'created_by_user_id',
        'flights_enabled',
    ];
    protected $casts = [
        'date' => 'date',
        'flights_enabled' => 'boolean',
    ];
    /**
     * Évènement "vol" (aéroport, stand, avion, legs) ou "hors vol"
     * (ex. soirée cinéma : simple participation, rien à réserver).
     * Les anciens évènements, sans type, sont considérés comme des vols.
     */
    public function isFlight(): bool
    {
        return ($this->type ?? self::TYPE_FLIGHT) === self::TYPE_FLIGHT;
    }

    /**
     * Réseaux de l'évènement : ['ivao'], ['vatsim'], ['ivao','vatsim'] ou [].
     * Stockés dans la colonne "network" sous la forme "ivao,vatsim".
     */
    public function networks(): array
    {
        return array_values(array_filter(explode(',', (string) $this->network)));
    }

    /**
     * Nettoie une valeur reçue du formulaire ("ivao,vatsim") : ne garde que
     * ivao / vatsim, dans cet ordre. Retourne null si rien n'est coché.
     */
    public static function cleanNetwork($value): ?string
    {
        $values = array_map('trim', explode(',', strtolower((string) $value)));
        $kept   = array_values(array_intersect(['ivao', 'vatsim'], $values));

        return $kept ? implode(',', $kept) : null;
    }

    public function legs()
    {
        return $this->hasMany(EventLeg::class, 'event_id')->orderBy('leg_number');
    }
    public function participants()
    {
        return $this->hasMany(EventParticipant::class, 'event_id')->whereNull('leg_id');
    }
    public function reservations()
    {
        return $this->hasMany(EventReservation::class, 'event_id');
    }
}