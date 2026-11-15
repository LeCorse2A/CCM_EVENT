<?php

namespace Modules\CCM_Events\Http\Controllers;

use App\Contracts\Controller;
use App\Models\Aircraft;
use App\Models\Enums\FlightType;
use App\Models\Flight;
use App\Services\GeoService;
use Illuminate\Support\Facades\Auth;
use Modules\CCM_Events\Entities\Event;
use Modules\CCM_Events\Entities\EventLeg;
use Modules\CCM_Events\Entities\EventReservation;

class EventFreeFlightController extends Controller
{
    /**
     * Page dédiée "planifier mon vol événement", pour UNE leg précise.
     * Le pilote choisit lui-même quelle leg il planifie (indépendamment
     * de tout état de positionnement admin) : départ/arrivée viennent
     * directement de la leg cliquée, l'avion vient de sa réservation.
     */
    public function show($id, $legId)
    {
        $user = Auth::user();

        $event = Event::with('legs')->findOrFail($id);

        $reservation = EventReservation::where('event_id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $leg = EventLeg::where('event_id', $id)->findOrFail($legId);

        $aircraft = Aircraft::with('airline')->find($reservation->aircraft_id);

        $depAirport = \App\Models\Airport::find($leg->departure_icao);
        $arrAirport = \App\Models\Airport::find($leg->arrival_icao);

        // Départ/arrivée toujours pris sur la leg choisie par le pilote,
        // indépendamment d'où l'admin a positionné l'avion pour l'instant.
        $fflight = Flight::updateOrCreate(
            [
                'route_code' => 'PF',
                'user_id'    => $user->id,
            ],
            [
                'airline_id'     => $user->airline_id,
                'flight_number'  => $user->id,
                'flight_type'    => 'E',
                'notes'          => $user->ident.' - '.$user->name_private,
                'dpt_airport_id' => $leg->departure_icao,
                'arr_airport_id' => $leg->arrival_icao,
                'active'         => 0,
                'visible'        => 0,
            ]
        );

        $mapFeatures = app(GeoService::class)->flightGeoJson($fflight);

        return view('ccm_events::public.freeflight', [
            'event'        => $event,
            'reservation'  => $reservation,
            'leg'          => $leg,
            'aircraft'     => $aircraft,
            'fflight'      => $fflight,
            'user'         => $user,
            'depAirport'   => $depAirport,
            'arrAirport'   => $arrAirport,
            'map_features' => $mapFeatures,
            'flight_types' => FlightType::select(true),
        ]);
    }
}