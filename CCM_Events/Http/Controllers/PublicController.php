<?php

namespace Modules\CCM_Events\Http\Controllers;

use App\Contracts\Controller;
use Modules\CCM_Events\Entities\Event;
use Modules\CCM_Events\Entities\EventParticipant;
use Modules\CCM_Events\Entities\EventReservation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PublicController extends Controller
{
    /**
     * Aéroports & stands : priorité à la valeur enregistrée en base
     * (onglet admin Configuration), fallback sur Config/config.php.
     * Même logique que getStoredCategories() côté EventController admin.
     */
    protected function getStoredAirports()
    {
        $row = DB::table('ccm_events_settings')->where('key', 'airports')->first();

        return $row ? json_decode($row->value, true) : config('ccm_events.airports');
    }

    public function index()
    {
        $now = Carbon::now();

        $events = Event::with(['legs', 'reservations'])
            ->where('status', 'active')
            ->where(function ($query) use ($now) {
                $query->where('date', '>', $now->format('Y-m-d'))
                    ->orWhere(function ($q) use ($now) {
                        $q->where('date', $now->format('Y-m-d'))
                            ->where('end_time', '>=', $now->format('H:i:s'));
                    });
            })
            ->orderBy('date')
            ->orderBy('start_time')
            ->get();

        $userId = Auth::id();

        $events->each(function ($event) use ($userId) {
            $event->my_participation = EventParticipant::where('event_id', $event->id)
                ->whereNull('leg_id')
                ->where('user_id', $userId)
                ->value('status');

            $event->my_reservation = EventReservation::where('event_id', $event->id)
                ->where('user_id', $userId)
                ->first();

            // Legs sur lesquelles le pilote a coché sa participation (pour
            // n'afficher que les vols correspondants sur la page publique)
            $event->my_leg_ids = EventParticipant::where('event_id', $event->id)
                ->whereNotNull('leg_id')
                ->where('user_id', $userId)
                ->pluck('leg_id')
                ->toArray();
        });

        return view('ccm_events::public.index', [
            'events'    => $events,
            'airports'  => $this->getStoredAirports(),
            'categories'=> config('ccm_events.categories'),
        ]);
    }

    /**
     * Catégories : valeur enregistrée en base (onglet admin), sinon config.
     */
    protected function getStoredCategories()
    {
        $row = DB::table('ccm_events_settings')->where('key', 'categories')->first();

        return $row ? json_decode($row->value, true) : config('ccm_events.categories');
    }

    /**
     * Page vitrine PUBLIQUE (/evenements), accessible sans connexion.
     * - Évènements à venir + évènements passés (annulés et supprimés exclus)
     * - Seuls les pilotes "participating" sont comptés (les déclinés sont ignorés)
     * - Les pilotes sont affichés uniquement par leur callsign (CCMxxxx)
     */
    public function showcase()
    {
        $now   = Carbon::now();
        $today = $now->format('Y-m-d');
        $time  = $now->format('H:i:s');

        $with = [
            'legs',
            'participants' => function ($q) {
                $q->where('status', 'participating')->with('user:id,callsign');
            },
        ];

        $upcoming = Event::with($with)
            ->where('status', 'active')
            ->where(function ($q) use ($today, $time) {
                $q->where('date', '>', $today)
                  ->orWhere(function ($q2) use ($today, $time) {
                      $q2->where('date', $today)->where('end_time', '>=', $time);
                  });
            })
            ->orderBy('date')
            ->orderBy('start_time')
            ->get();

        $past = Event::with($with)
            ->where('status', 'active')
            ->where(function ($q) use ($today, $time) {
                $q->where('date', '<', $today)
                  ->orWhere(function ($q2) use ($today, $time) {
                      $q2->where('date', $today)->where('end_time', '<', $time);
                  });
            })
            ->orderByDesc('date')
            ->orderByDesc('start_time')
            ->get();

        // Liste des callsigns "CCMxxxx" pour chaque évènement
        $mapCallsigns = function ($event) {
            $event->pilot_callsigns = $event->participants
                ->map(function ($p) {
                    return optional($p->user)->callsign ? 'CCM'.$p->user->callsign : null;
                })
                ->filter()
                ->sort(SORT_NATURAL)
                ->values();
        };
        $upcoming->each($mapCallsigns);
        $past->each($mapCallsigns);

        $totalEvents         = $past->count();
        $totalParticipations = $past->sum(function ($e) { return $e->participants->count(); });
        $average             = $totalEvents > 0 ? round($totalParticipations / $totalEvents, 1) : 0;

        return view('ccm_events::public.showcase', [
            'upcoming'            => $upcoming,
            'past'                => $past,
            'categories'          => $this->getStoredCategories(),
            'totalEvents'         => $totalEvents,
            'totalParticipations' => $totalParticipations,
            'average'             => $average,
        ]);
    }

    public function participate($id)
    {
        EventParticipant::updateOrCreate(
            ['event_id' => $id, 'leg_id' => null, 'user_id' => Auth::id()],
            ['status' => 'participating']
        );

        return response()->json(['success' => true]);
    }

    public function decline($id)
    {
        EventParticipant::updateOrCreate(
            ['event_id' => $id, 'leg_id' => null, 'user_id' => Auth::id()],
            ['status' => 'not_participating']
        );

        // Si le pilote avait réservé un avion pour cet event, on libère la réservation
        EventReservation::where('event_id', $id)
            ->where('user_id', Auth::id())
            ->delete();

        return response()->json(['success' => true]);
    }

    /**
     * Liste des participants d'un event, avec callsign et détails de
     * la réservation (aéroport/stand/avion) s'ils en ont une.
     */
    public function participants($id)
    {
        $participants = EventParticipant::with('user')
            ->where('event_id', $id)
            ->whereNull('leg_id')
            ->where('status', 'participating')
            ->get()
            ->map(function ($p) use ($id) {
                $callsign = optional($p->user)->callsign ? 'CCM'.$p->user->callsign : 'Pilote #'.$p->user_id;
                $name     = optional($p->user)->name_private ?? optional($p->user)->name;

                $p->user_callsign = $name ? $callsign.' - '.$name : $callsign;

                $reservation = EventReservation::with('aircraft')
                    ->where('event_id', $id)
                    ->where('user_id', $p->user_id)
                    ->first();

                $p->reserved_airport  = $reservation->airport_icao ?? null;
                $p->reserved_stand    = $reservation->stand ?? null;
                $p->reserved_aircraft = $reservation && $reservation->aircraft
                    ? $reservation->aircraft->icao.' | '.$reservation->aircraft->registration.($reservation->aircraft->name ? ' - '.$reservation->aircraft->name : '')
                    : null;

                return $p;
            })
            // Tri par stand (1, 2, 3 … 10 en ordre naturel), les pilotes sans stand à la fin
            ->sort(function ($a, $b) {
                $sa = $a->reserved_stand;
                $sb = $b->reserved_stand;

                if ($sa === null && $sb === null) return 0;
                if ($sa === null) return 1;
                if ($sb === null) return -1;

                return strnatcasecmp((string) $sa, (string) $sb)
                    ?: strcmp((string) $a->reserved_airport, (string) $b->reserved_airport);
            })
            ->values();

        // Pilotes ayant cliqué sur "Je décline" (ligne conservée en base avec le statut not_participating)
        $decliners = EventParticipant::with('user')
            ->where('event_id', $id)
            ->whereNull('leg_id')
            ->where('status', 'not_participating')
            ->get()
            ->map(function ($p) {
                $callsign = optional($p->user)->callsign ? 'CCM'.$p->user->callsign : 'Pilote #'.$p->user_id;
                $name     = optional($p->user)->name_private ?? optional($p->user)->name;

                return ['user_callsign' => $name ? $callsign.' - '.$name : $callsign];
            })
            ->sortBy('user_callsign', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        return response()->json(['success' => true, 'participants' => $participants, 'decliners' => $decliners]);
    }

    /**
     * Stands d'un aéroport. Si event_id est fourni, renvoie aussi la liste
     * des stands déjà réservés sur cet évènement pour cet aéroport, afin que
     * la modale de réservation puisse les griser.
     */
    public function stands(Request $request, $icao)
    {
        $icao      = strtoupper($icao);
        $allStands = $this->getStoredAirports()[$icao] ?? [];

        $reserved = [];
        if ($request->filled('event_id')) {
            $reserved = EventReservation::where('event_id', (int) $request->query('event_id'))
                ->where('airport_icao', $icao)
                ->whereNotNull('stand')
                ->pluck('stand')
                ->all();
        }

        return response()->json([
            'success'  => true,
            'stands'   => $allStands,
            'reserved' => $reserved,
        ]);
    }

    /**
     * Avions disponibles pour le pilote connecté sur un évènement donné
     * (utilisés dans la modale de réservation côté page publique) :
     * avions dont le subfleet correspond à un type rating possédé par le
     * pilote (ou déjà en bid chez lui), en excluant ceux déjà réservés par
     * un AUTRE pilote sur cet évènement (pour éviter les doublons).
     */
    public function myAircraft($id)
    {
        $userId = Auth::id();

        $subfleetIds = \Illuminate\Support\Facades\DB::table('typerating_subfleet')
            ->join('typerating_user', 'typerating_subfleet.typerating_id', '=', 'typerating_user.typerating_id')
            ->where('typerating_user.user_id', $userId)
            ->pluck('typerating_subfleet.subfleet_id');

        $reservedAircraftIds = EventReservation::where('event_id', $id)
            ->where('user_id', '!=', $userId)
            ->whereNotNull('aircraft_id')
            ->pluck('aircraft_id');

        $aircraft = \App\Models\Aircraft::where(function ($query) use ($subfleetIds, $userId) {
                $query->whereIn('subfleet_id', $subfleetIds)
                    ->orWhereHas('bid', fn($q) => $q->where('user_id', $userId));
            })
            ->whereNotIn('id', $reservedAircraftIds)
            ->get();

        return response()->json(['success' => true, 'aircraft' => $aircraft]);
    }

    /**
     * Legs d'un évènement (lecture seule, pour la modale de réservation
     * publique — cocher sur quelle(s) leg(s) on participe).
     */
    public function legs($id)
    {
        $legs = \Modules\CCM_Events\Entities\EventLeg::where('event_id', $id)
            ->orderBy('leg_number')
            ->get();

        return response()->json(['success' => true, 'legs' => $legs]);
    }

    /**
     * Legs sur lesquelles le pilote connecté a déjà coché sa participation
     * pour cet évènement (pré-remplissage des checkboxes de la modale).
     */
    public function legParticipants($id)
    {
        $legIds = EventParticipant::where('event_id', $id)
            ->whereNotNull('leg_id')
            ->where('user_id', Auth::id())
            ->pluck('leg_id');

        return response()->json(['success' => true, 'legs' => $legIds]);
    }

    public function storeReservation(Request $request, $id)
    {
        $validated = $request->validate([
            'airport_icao' => 'required|string|size:4',
            'stand'        => 'nullable|string',
            'aircraft_id'  => 'required|integer',
            'legs'         => 'nullable|array',
            'legs.*'       => 'integer',
        ], [
            'airport_icao.required' => 'L\'aéroport est obligatoire.',
            'airport_icao.size'     => 'Le code ICAO doit faire 4 lettres.',
            'aircraft_id.required'  => 'Vous devez choisir un avion.',
        ]);

        $event = Event::findOrFail($id);

        // Évènement hors vol : rien à réserver (la participation se fait via /participate)
        if (!$event->isFlight()) {
            return response()->json(['success' => false, 'message' => 'Cet évènement ne nécessite pas de réservation.'], 422);
        }

        $icao  = strtoupper($validated['airport_icao']);
        $stand = $validated['stand'] ?? null;

        // ── Legs : au moins une obligatoire si l'évènement en a ──
        // On ne garde que les legs qui appartiennent bien à cet évènement
        $eventLegIds = \Modules\CCM_Events\Entities\EventLeg::where('event_id', $id)->pluck('id')->all();
        $legIds = array_values(array_intersect(array_map('intval', $validated['legs'] ?? []), $eventLegIds));

        if (!empty($eventLegIds) && empty($legIds)) {
            return response()->json(['success' => false, 'message' => 'Vous devez cocher au moins une leg.'], 422);
        }

        // ── Stand : obligatoire si l'aéroport a des stands paramétrés ──
        $standsAeroport = array_map('strval', $this->getStoredAirports()[$icao] ?? []);

        if (!empty($standsAeroport)) {
            if (!$stand) {
                return response()->json(['success' => false, 'message' => 'Vous devez choisir un emplacement (stand).'], 422);
            }
            if (!in_array((string) $stand, $standsAeroport, true)) {
                return response()->json(['success' => false, 'message' => 'Ce stand n\'existe pas pour cet aéroport.'], 422);
            }
        } else {
            // Aucun stand paramétré pour cet aéroport : on n'enregistre pas de stand
            $stand = null;
        }

        $standPris = 'Ce stand vient d\'être réservé par un autre pilote. Choisissez-en un autre.';

        // Vérification explicite : stand déjà pris par un AUTRE pilote sur cet évènement
        if ($stand) {
            $pris = EventReservation::where('event_id', $id)
                ->where('airport_icao', $icao)
                ->where('stand', $stand)
                ->where('user_id', '!=', Auth::id())
                ->exists();

            if ($pris) {
                return response()->json(['success' => false, 'message' => $standPris], 422);
            }
        }

        try {
            $reservation = EventReservation::updateOrCreate(
                ['event_id' => $id, 'user_id' => Auth::id()],
                [
                    'airport_icao'  => $icao,
                    'stand'         => $stand,
                    'aircraft_id'   => $validated['aircraft_id'],
                    'flight_active' => $event->flights_enabled,
                ]
            );
        } catch (\Illuminate\Database\QueryException $e) {
            // 1062 = doublon sur l'index unique (deux pilotes qui valident en même temps)
            if (($e->errorInfo[1] ?? null) == 1062) {
                return response()->json(['success' => false, 'message' => $standPris], 422);
            }
            throw $e;
        }

        // La réservation a réussi : on enregistre la participation
        // (avant, c'était le JS qui l'appelait en premier, même si la réservation échouait ensuite)
        EventParticipant::updateOrCreate(
            ['event_id' => $id, 'leg_id' => null, 'user_id' => Auth::id()],
            ['status' => 'participating']
        );

        // ── Sync des legs cochées (déjà filtrées et vérifiées plus haut) ──
        EventParticipant::where('event_id', $id)
            ->whereNotNull('leg_id')
            ->where('user_id', Auth::id())
            ->delete();

        foreach ($legIds as $legId) {
            EventParticipant::updateOrCreate(
                ['event_id' => $id, 'leg_id' => $legId, 'user_id' => Auth::id()],
                ['status' => 'participating']
            );
        }

        return response()->json(['success' => true, 'reservation' => $reservation]);
    }

    public function myReservation($id)
    {
        $reservation = EventReservation::where('event_id', $id)->where('user_id', Auth::id())->first();

        return response()->json(['success' => true, 'reservation' => $reservation]);
    }

    public function allReservations($id)
    {
        $reservations = EventReservation::with(['user', 'aircraft'])->where('event_id', $id)->get();

        return response()->json(['success' => true, 'reservations' => $reservations]);
    }

    public function cancelReservation($reservation_id)
    {
        $reservation = EventReservation::where('id', $reservation_id)->where('user_id', Auth::id())->firstOrFail();
        $eventId = $reservation->event_id;

        $reservation->delete();

        // "Annuler" = je ne participe plus du tout (contrairement à "décliner",
        // qui indique explicitement une absence) : on retire aussi la participation.
        EventParticipant::where('event_id', $eventId)
            ->where('user_id', Auth::id())
            ->delete();

        return response()->json(['success' => true]);
    }
}