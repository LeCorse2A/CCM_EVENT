<?php
namespace Modules\CCM_Events\Http\Controllers\Admin;
use App\Contracts\Controller;
use Modules\CCM_Events\Entities\Event;
use Modules\CCM_Events\Entities\EventReservation;
class EventFlightController extends Controller
{
    /**
     * Active/désactive le vol lié à l'évènement (flag sur l'évènement
     * lui-même, cf. décision : c'est le responsable qui active le vol en
     * question, pas d'automatisation). Toute réservation créée après
     * l'activation hérite automatiquement de flight_active.
     */
    public function toggle($id)
    {
        $event = Event::findOrFail($id);

        if (!$event->isFlight()) {
            return response()->json(['success' => false, 'message' => 'Cet évènement est hors vol.'], 422);
        }

        $active = !$event->flights_enabled;

        $event->flights_enabled = $active;
        $event->save();

        // On garde la synchro des réservations existantes pour ne pas les
        // laisser dans un état incohérent par rapport au flag de l'évènement.
        $event->reservations()->update(['flight_active' => $active]);

        return response()->json([
            'success' => true,
            'active'  => $active,
            'message' => $active ? 'Vols activés pour cet évènement' : 'Vols désactivés pour cet évènement',
        ]);
    }
    public function positionAircraft($reservation_id)
    {
        $reservation = EventReservation::with(['aircraft', 'event.legs', 'user'])->findOrFail($reservation_id);
        if (!$reservation->aircraft) {
            return response()->json(['success' => false, 'message' => 'Avion introuvable']);
        }
        $reservation->original_airport_id = $reservation->aircraft->airport_id;
        $reservation->save();
        $reservation->aircraft->airport_id = $reservation->airport_icao;
        $reservation->aircraft->save();
        // On cherche la leg qui PART de l'aéroport où le pilote est positionné
        // (ex: event LFKJ→LFRB→LFKB, réservation à LFKJ → doit donner LFRB, pas LFKB)
        $matchingLeg = $reservation->event->legs
            ->firstWhere('departure_icao', $reservation->airport_icao);
        $arrivalIcao = optional($matchingLeg)->arrival_icao
            ?? optional($reservation->event->legs->last())->arrival_icao;
        \App\Models\Flight::updateOrCreate(
            ['route_code' => 'PF', 'user_id' => $reservation->user_id],
            [
                'dpt_airport_id' => $reservation->airport_icao,
                'arr_airport_id' => $arrivalIcao ?: $reservation->airport_icao,
                'airline_id'     => optional($reservation->user)->airline_id,
                'flight_number'  => $reservation->user_id,
                'flight_type'    => 'E',
                'active'         => 0,
                'visible'        => 0,
            ]
        );
        return response()->json([
            'success'  => true,
            'message'  => 'Avion positionné à '.$reservation->airport_icao,
            'original' => $reservation->original_airport_id,
        ]);
    }
    public function returnAircraft($reservation_id)
    {
        $reservation = EventReservation::with('aircraft')->findOrFail($reservation_id);
        if (!$reservation->aircraft) {
            return response()->json(['success' => false, 'message' => 'Avion introuvable']);
        }
        if (!$reservation->original_airport_id) {
            return response()->json(['success' => false, 'message' => "Position d'origine inconnue"]);
        }
        $reservation->aircraft->airport_id = $reservation->original_airport_id;
        $reservation->aircraft->save();
        $reservation->original_airport_id = null;
        $reservation->save();
        return response()->json(['success' => true, 'message' => 'Avion retourné à '.$reservation->aircraft->airport_id]);
    }
}