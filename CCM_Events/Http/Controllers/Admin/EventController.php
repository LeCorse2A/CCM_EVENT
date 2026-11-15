<?php

namespace Modules\CCM_Events\Http\Controllers\Admin;

use App\Contracts\Controller;
use Modules\CCM_Events\Entities\Event;
use Modules\CCM_Events\Entities\EventLeg;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class EventController extends Controller
{
    /**
     * Nettoyage "maison" (pas de dépendance Composer) du HTML produit par
     * l'éditeur riche Quill côté admin. On ne garde que les balises
     * p/br/strong/b/em/i/u/span, et sur les span, uniquement un style
     * font-size en pixels (10 à 48px). Tout le reste — balises interdites
     * (script, iframe, ...), attributs (onclick, href, ...), styles CSS
     * autres que font-size — est supprimé.
     */
    protected function purifyDescription(?string $html): ?string
    {
        if (!$html) {
            return $html;
        }

        // 1) Ne garder que les balises autorisées (le contenu des balises
        //    retirées, comme <script>, reste en texte brut — donc inerte).
        $html = strip_tags($html, '<p><br><strong><b><em><i><u><span>');

        // 2) Sur les <span>, ne conserver qu'un style="font-size:Npx" propre,
        //    borné à une plage raisonnable ; tout autre attribut disparaît.
        $html = preg_replace_callback('/<span\b[^>]*>/i', function ($m) {
            if (preg_match('/style\s*=\s*"([^"]*)"/i', $m[0], $styleMatch)
                && preg_match('/font-size\s*:\s*(\d{1,3})px/i', $styleMatch[1], $sizeMatch)) {
                $size = max(10, min(48, (int) $sizeMatch[1]));
                return '<span style="font-size:' . $size . 'px">';
            }
            return '<span>';
        }, $html);

        // 3) Sur les autres balises autorisées, supprimer tout attribut résiduel.
        $html = preg_replace('/<(p|br|strong|b|em|i|u)\b[^>]*>/i', '<$1>', $html);

        return $html;
    }

    public function index(Request $request)
    {
        $currentMonth = (int) $request->get('month', now()->month);
        $currentYear  = (int) $request->get('year', now()->year);

        if ($currentMonth < 1) {
            $currentMonth = 12;
            $currentYear--;
        } elseif ($currentMonth > 12) {
            $currentMonth = 1;
            $currentYear++;
        }

        $firstDay = Carbon::createFromDate($currentYear, $currentMonth, 1);
        $lastDay  = $firstDay->copy()->endOfMonth();

        $events = Event::with(['legs', 'reservations'])
            ->whereBetween('date', [$firstDay->format('Y-m-d'), $lastDay->format('Y-m-d')])
            ->orderBy('date')
            ->orderBy('start_time')
            ->get();

        return view('ccm_events::admin.index', [
            'events'       => $events,
            'currentMonth' => $currentMonth,
            'currentYear'  => $currentYear,
            'categories'   => $this->getStoredCategories(),
        ]);
    }

    public function show($id)
    {
        $event = Event::with(['legs', 'reservations.user', 'reservations.aircraft'])->findOrFail($id);

        $event->reservations->each(function ($reservation) {
            $reservation->user_name = optional($reservation->user)->name;
        });

        return response()->json($event);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'       => 'required|string|max:255',
            'date'        => 'required|date_format:Y-m-d',
            'start_time'  => 'required|date_format:H:i',
            'end_time'    => 'required|date_format:H:i|after:start_time',
            'description' => 'nullable|string',
            'category'    => 'nullable|string',
            'type'        => 'nullable|in:flight,non_flight',
            'status'      => 'nullable|in:active,cancelled',
            'banner'      => 'nullable|image|mimes:jpg,jpeg,png,gif,bmp,webp,avif|max:20480',
        ]);

        $validated['status'] = $validated['status'] ?? 'active';
        $validated['type']   = $validated['type'] ?? Event::TYPE_FLIGHT;
        $validated['description'] = $this->purifyDescription($validated['description'] ?? null);

        $bannerFile = $request->file('banner');
        unset($validated['banner']);

        $event = Event::create($validated);

        if ($bannerFile) {
            $event->banner_path = $this->storeEventBanner($event->id, $bannerFile);
            $event->save();
        }

        return response()->json(['success' => true, 'event' => $event], 201);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'title'         => 'required|string|max:255',
            'date'          => 'required|date_format:Y-m-d',
            'start_time'    => 'required|date_format:H:i',
            'end_time'      => 'required|date_format:H:i|after:start_time',
            'description'   => 'nullable|string',
            'category'      => 'nullable|string',
            'type'          => 'nullable|in:flight,non_flight',
            'status'        => 'nullable|in:active,cancelled',
            'banner'        => 'nullable|image|mimes:jpg,jpeg,png,gif,bmp,webp,avif|max:20480',
            'remove_banner' => 'nullable|boolean',
        ]);

        // Pas de type envoyé : on ne touche pas à celui de l'évènement
        if (empty($validated['type'])) {
            unset($validated['type']);
        }

        $validated['description'] = $this->purifyDescription($validated['description'] ?? null);

        $event = Event::findOrFail($id);

        $bannerFile = $request->file('banner');
        unset($validated['banner'], $validated['remove_banner']);

        $event->update($validated);

        if ($bannerFile) {
            $this->deleteEventBanner($event->banner_path);
            $event->banner_path = $this->storeEventBanner($event->id, $bannerFile);
            $event->save();
        } elseif ($request->boolean('remove_banner')) {
            $this->deleteEventBanner($event->banner_path);
            $event->banner_path = null;
            $event->save();
        }

        return response()->json(['success' => true, 'event' => $event]);
    }

    public function destroy($id)
    {
        $event = Event::findOrFail($id);
        $this->deleteEventBanner($event->banner_path);
        $event->delete();

        return response()->json(['success' => true]);
    }

    /**
     * Données pour l'onglet "Vols & Réservations" : events à venir avec
     * leurs réservations (pilote, avion, position) et leurs legs.
     */
    public function flightsData()
    {
        $now = Carbon::now();

        $events = Event::with(['legs', 'reservations.user', 'reservations.aircraft'])
            ->where('status', 'active')
            ->where('type', Event::TYPE_FLIGHT)
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

        $events->each(function ($event) {
            $event->reservations->each(function ($reservation) {
                $reservation->user_name = optional($reservation->user)->name_private
                    ?? optional($reservation->user)->name;
            });
        });

        return response()->json(['success' => true, 'events' => $events]);
    }

    /**
     * Seuils (absences consécutives) et textes des mails de rappel.
     * Valeurs enregistrées dans l'onglet Configuration, sinon valeurs par défaut.
     */
    public static function reminderDefaults(): array
    {
        return [
            'thresholds' => ['reminder' => 3, 'warning' => 5, 'exclusion' => 7],
            'mails' => [
                'reminder' => [
                    'subject' => 'Air Corsica Virtuel — On ne vous voit plus aux évènements !',
                    'body'    => "Bonjour {prenom},\n\n"
                        ."Nous avons remarqué que vous n'avez participé à aucun des {nb_absences} derniers évènements d'Air Corsica Virtuel.\n\n"
                        ."Les évènements sont au cœur de la vie de la compagnie : ce sont des moments où l'on vole ensemble, où l'on se retrouve et où la communauté se construit. Votre présence compte !\n\n"
                        ."Retrouvez les prochains évènements sur le site, rubrique Évènements. Si vous ne pouvez pas venir, pensez à cliquer sur « Je décline », ou à déclarer une absence si vous êtes indisponible quelque temps.\n\n"
                        ."À très bientôt dans les airs,\n"
                        ."Le staff Air Corsica Virtuel",
                ],
                'warning' => [
                    'subject' => 'Air Corsica Virtuel — Avertissement : participation aux évènements',
                    'body'    => "Bonjour {prenom},\n\n"
                        ."Malgré notre précédent rappel, vous n'avez participé à aucun des {nb_absences} derniers évènements d'Air Corsica Virtuel, sans les décliner ni déclarer d'absence.\n\n"
                        ."La participation aux évènements fait partie de la vie de la compagnie et est attendue de chaque pilote. Sans participation de votre part lors des prochains évènements, le staff pourra décider de retirer votre compte.\n\n"
                        ."Si vous traversez une période d'indisponibilité, déclarez simplement une absence sur le site : elle sera prise en compte.\n\n"
                        ."Cordialement,\n"
                        ."Le staff Air Corsica Virtuel",
                ],
                'exclusion' => [
                    'subject' => 'Air Corsica Virtuel — Retrait de votre compte',
                    'body'    => "Bonjour {prenom},\n\n"
                        ."Malgré nos rappels et notre avertissement, vous n'avez pas participé ni répondu aux {nb_absences} derniers évènements d'Air Corsica Virtuel.\n\n"
                        ."Les évènements faisant partie intégrante de la vie de la compagnie, le staff a décidé de retirer votre compte pilote.\n\n"
                        ."Nous vous remercions pour le temps passé parmi nous. Si vous souhaitez revenir plus tard et vous investir dans la vie de la compagnie, n'hésitez pas à nous recontacter.\n\n"
                        ."Cordialement,\n"
                        ."Le staff Air Corsica Virtuel",
                ],
            ],
        ];
    }

    public static function reminderSettings(): array
    {
        $defaults = self::reminderDefaults();
        $row = DB::table('ccm_events_settings')->where('key', 'reminders')->value('value');
        $stored = $row ? (json_decode($row, true) ?: []) : [];

        return array_replace_recursive($defaults, $stored);
    }

    /**
     * Données pour l'onglet "Statistiques".
     * - Pilotes comptés : comptes actifs ou en congé (pas les supprimés),
     *   moins ceux exclus dans l'onglet Configuration (admins qui ne volent pas).
     * - Tous les évènements sont comptés (vol, hors vol, annulés, supprimés).
     * - Un pilote n'est compté sur un évènement que s'il était déjà inscrit
     *   sur le site à cette date, et s'il n'était pas en absence (table pilot_leaves du module RH).
     * - Série : évènements VOL passés uniquement (ni annulés, ni supprimés).
     *   "Je décline" est neutre : il ne casse pas une série et ne compte pas comme absence.
     * - ?months=3|6 : limite aux évènements des X derniers mois (0 = tout).
     */
    public function statsData(Request $request)
    {
        $months   = (int) $request->query('months', 0);
        $settings = self::reminderSettings();
        $th       = $settings['thresholds'];

        $excluded = DB::table('ccm_events_settings')->where('key', 'stats_excluded_users')->value('value');
        $excluded = $excluded ? array_map('intval', json_decode($excluded, true) ?: []) : [];

        $users = \App\Models\User::whereIn('state', [1, 3]) // 1 = actif, 3 = en congé
            ->whereNotIn('id', $excluded)
            ->where('name', '!=', 'Deleted User')
            ->orderBy('callsign')
            ->get();

        $pilots = $users->mapWithKeys(fn ($u) => [
            $u->id => ($u->callsign ? 'CCM'.$u->callsign : 'Pilote #'.$u->id).' - '.($u->name_private ?? $u->name),
        ]);
        // Date d'inscription de chaque pilote sur le site (Y-m-d)
        $joined = $users->mapWithKeys(fn ($u) => [$u->id => optional($u->created_at)->format('Y-m-d') ?? '0000-00-00']);

        $pilotIds = $pilots->keys()->all();

        // Absences déclarées (module RH)
        $leaves = Schema::hasTable('pilot_leaves')
            ? DB::table('pilot_leaves')->whereIn('user_id', $pilotIds)->get(['user_id', 'start_date', 'end_date'])->groupBy('user_id')
            : collect();

        $isAbsent = function ($userId, $day) use ($leaves) {
            foreach ($leaves->get($userId, []) as $l) {
                if (substr($l->start_date, 0, 10) <= $day && substr($l->end_date, 0, 10) >= $day) {
                    return true;
                }
            }
            return false;
        };

        // Dernier mail envoyé à chaque pilote
        $lastReminders = Schema::hasTable('ccm_event_reminders')
            ? DB::table('ccm_event_reminders')->whereIn('user_id', $pilotIds)->orderByDesc('created_at')->get()->unique('user_id')->keyBy('user_id')
            : collect();

        // Nombre de mails envoyés par pilote et par type
        $mailCounts = [];
        if (Schema::hasTable('ccm_event_reminders')) {
            DB::table('ccm_event_reminders')->whereIn('user_id', $pilotIds)
                ->select('user_id', 'level', DB::raw('COUNT(*) as n'))
                ->groupBy('user_id', 'level')->get()
                ->each(function ($row) use (&$mailCounts) { $mailCounts[$row->user_id][$row->level] = (int) $row->n; });
        }

        $events = Event::withTrashed()
            ->when($months > 0, fn ($q) => $q->where('date', '>=', now()->subMonths($months)->format('Y-m-d')))
            ->orderByDesc('date')
            ->orderByDesc('start_time')
            ->get();

        // Toutes les réponses (participation globale, hors legs) des pilotes comptés
        $answers = DB::table('ccm_event_participants')
            ->whereNull('leg_id')
            ->whereIn('user_id', $pilotIds)
            ->whereIn('event_id', $events->pluck('id'))
            ->get(['event_id', 'user_id', 'status']);

        // [event_id][user_id] => status
        $statusOf = [];
        foreach ($answers as $a) {
            $statusOf[$a->event_id][$a->user_id] = $a->status;
        }

        $eventsOut = $events->map(function ($e) use ($pilotIds, $joined, $isAbsent, $statusOf) {
            $day = optional($e->date)->format('Y-m-d');
            $going = []; $declined = []; $noAnswer = []; $absent = [];

            foreach ($pilotIds as $uid) {
                $status = $statusOf[$e->id][$uid] ?? null;
                if ($status === 'participating')        { $going[] = $uid; continue; }
                if ($status === 'not_participating')    { $declined[] = $uid; continue; }
                if ($joined[$uid] > $day)               { continue; }                 // pas encore inscrit
                if ($isAbsent($uid, $day))              { $absent[] = $uid; continue; } // absence déclarée
                $noAnswer[] = $uid;
            }

            $base = count($going) + count($declined) + count($noAnswer);

            return [
                'id'        => $e->id,
                'title'     => $e->title,
                'date'      => optional($e->date)->format('d/m/Y'),
                'date_sort' => $day,
                'is_flight' => $e->isFlight(),
                'cancelled' => $e->status === 'cancelled',
                'deleted'   => $e->trashed(),
                'eligible'  => $base,
                'going'     => $going,
                'declined'  => $declined,
                'no_answer' => $noAnswer,
                'absent'    => $absent,
                'rate'      => $base ? round(count($going) * 100 / $base) : 0,
            ];
        })->values();

        // Évènements servant à la série : vols passés, ni annulés ni supprimés, du plus récent au plus ancien
        $today = now()->format('Y-m-d');
        $seriesEvents = $eventsOut->filter(fn ($e) => $e['is_flight'] && !$e['cancelled'] && !$e['deleted'] && $e['date_sort'] < $today)
            ->sortByDesc('date_sort')->values();

        $pilotsOut = $pilots->map(function ($label, $userId) use ($eventsOut, $events, $seriesEvents, $th, $lastReminders, $mailCounts) {
            $going = 0; $declined = 0; $noAnswer = 0; $absent = 0; $lastId = null;

            foreach ($eventsOut as $e) { // du plus récent au plus ancien
                if (in_array($userId, $e['going']))     { $going++; $lastId = $lastId ?? $e['id']; }
                elseif (in_array($userId, $e['declined']))  { $declined++; }
                elseif (in_array($userId, $e['no_answer'])) { $noAnswer++; }
                elseif (in_array($userId, $e['absent']))    { $absent++; }
            }
            $base = $going + $declined + $noAnswer;

            // Série en cours
            $streakType = null; $streak = 0;
            foreach ($seriesEvents as $e) {
                if (in_array($userId, $e['going']))         { $type = 'going'; }
                elseif (in_array($userId, $e['no_answer'])) { $type = 'missed'; }
                else { continue; } // décliné, absent ou pas encore inscrit : neutre

                if ($streakType === null) { $streakType = $type; }
                if ($type !== $streakType) { break; }
                $streak++;
            }

            $missed = $streakType === 'missed' ? $streak : 0;
            $level = null;
            if ($missed >= $th['exclusion'])    { $level = 'exclusion'; }
            elseif ($missed >= $th['warning'])  { $level = 'warning'; }
            elseif ($missed >= $th['reminder']) { $level = 'reminder'; }

            $last = $lastId ? $events->firstWhere('id', $lastId) : null;
            $rem  = $lastReminders->get($userId);

            return [
                'id'          => $userId,
                'label'       => $label,
                'joined'      => null,
                'eligible'    => $base,
                'going'       => $going,
                'declined'    => $declined,
                'no_answer'   => $noAnswer,
                'absent'      => $absent,
                'rate'        => $base ? round($going * 100 / $base) : 0,
                'last_title'  => $last ? $last->title : null,
                'last_date'   => $last ? optional($last->date)->format('Y-m-d') : null,
                'streak_type' => $streakType,
                'streak'      => $streak,
                'streak_sort' => $streakType === 'missed' ? -$streak : $streak, // pour trier : absents en premier
                'level'       => $level,
                'mail_counts' => [
                    'reminder'  => $mailCounts[$userId]['reminder'] ?? 0,
                    'warning'   => $mailCounts[$userId]['warning'] ?? 0,
                    'exclusion' => $mailCounts[$userId]['exclusion'] ?? 0,
                ],
                'last_mail'   => $rem ? ['level' => $rem->level, 'date' => Carbon::parse($rem->created_at)->format('Y-m-d')] : null,
            ];
        })->values();

        // Date d'inscription affichée
        $pilotsOut = $pilotsOut->map(function ($p) use ($joined) {
            $j = $joined[$p['id']];
            $p['joined'] = $j !== '0000-00-00' ? Carbon::parse($j)->format('d/m/Y') : null;
            return $p;
        });

        return response()->json([
            'success'    => true,
            'months'     => $months,
            'thresholds' => $th,
            'mails'      => $settings['mails'],   // modèles, pour pré-remplir la fenêtre d'envoi
            'nb_pilots'  => count($pilotIds),
            'nb_events'  => $events->count(),
            'pilots'     => $pilots,     // id => libellé, pour afficher les listes de noms
            'events'     => $eventsOut,
            'by_pilot'   => $pilotsOut,
        ]);
    }

    /**
     * Envoi des mails de rappel / d'avertissement aux pilotes sélectionnés.
     * Corps : level (reminder|warning|exclusion), pilots: [{id, absences}], subject/body optionnels
     */
    public function sendReminders(Request $request)
    {
        $validated = $request->validate([
            'level'            => 'required|in:reminder,warning,exclusion',
            'subject'          => 'nullable|string|max:255',   // texte modifié dans la fenêtre d'envoi (optionnel)
            'body'             => 'nullable|string',
            'pilots'           => 'required|array|min:1',
            'pilots.*.id'      => 'required|integer',
            'pilots.*.absences'=> 'nullable|integer',
        ]);

        if (!Schema::hasTable('ccm_event_reminders')) {
            return response()->json(['success' => false, 'message' => 'La table ccm_event_reminders n\'existe pas : lancez d\'abord la requête SQL.'], 422);
        }

        $mail = self::reminderSettings()['mails'][$validated['level']];
        if (!empty($validated['subject'])) { $mail['subject'] = $validated['subject']; }
        if (!empty($validated['body']))    { $mail['body']    = $validated['body']; }
        $sent = 0; $failed = [];

        foreach ($validated['pilots'] as $p) {
            $user = \App\Models\User::find($p['id']);
            if (!$user || !$user->email) {
                $failed[] = 'Pilote #'.$p['id'].' (pas d\'email)';
                continue;
            }

            $prenom = trim(preg_split('/[\s.]+/', (string) $user->name)[0] ?? '') ?: $user->name;
            $vars = [
                '{prenom}'      => $prenom,
                '{callsign}'    => $user->callsign ? 'CCM'.$user->callsign : '',
                '{nb_absences}' => (int) ($p['absences'] ?? 0),
            ];
            $subject = strtr($mail['subject'], $vars);
            $body    = strtr($mail['body'], $vars);

            try {
                Mail::raw($body, function ($m) use ($user, $subject) {
                    $m->to($user->email)->subject($subject);
                });

                DB::table('ccm_event_reminders')->insert([
                    'user_id'    => $user->id,
                    'level'      => $validated['level'],
                    'absences'   => (int) ($p['absences'] ?? 0),
                    'sent_by'    => Auth::id(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $sent++;
            } catch (\Throwable $ex) {
                $failed[] = ($user->callsign ? 'CCM'.$user->callsign : 'Pilote #'.$user->id).' ('.$ex->getMessage().')';
            }
        }

        $msg = $sent.' mail(s) envoyé(s).';
        if ($failed) {
            $msg .= "\nÉchec pour : ".implode(', ', $failed);
        }

        return response()->json(['success' => $sent > 0, 'sent' => $sent, 'failed' => $failed, 'message' => $msg]);
    }

    public function getLegs($id)
    {
        $legs = EventLeg::where('event_id', $id)->orderBy('leg_number')->get();

        return response()->json(['success' => true, 'legs' => $legs]);
    }

    public function storeLeg(Request $request, $id)
    {
        $validated = $request->validate([
            'departure_icao' => 'required|string|size:4',
            'arrival_icao'   => 'required|string|size:4',
        ]);

        if (!Event::findOrFail($id)->isFlight()) {
            return response()->json(['success' => false, 'message' => 'Un évènement hors vol n\'a pas de trajets.'], 422);
        }

        $legNumber = EventLeg::where('event_id', $id)->max('leg_number') + 1;

        $leg = EventLeg::create([
            'event_id'       => $id,
            'leg_number'     => $legNumber,
            'departure_icao' => strtoupper($validated['departure_icao']),
            'arrival_icao'   => strtoupper($validated['arrival_icao']),
        ]);

        return response()->json(['success' => true, 'leg' => $leg]);
    }

    public function destroyLeg($id, $leg_id)
    {
        $leg = EventLeg::where('id', $leg_id)->where('event_id', $id)->firstOrFail();
        $leg->delete();

        EventLeg::where('event_id', $id)->orderBy('leg_number')->get()
            ->each(function ($l, $index) {
                $l->leg_number = $index + 1;
                $l->save();
            });

        return response()->json(['success' => true]);
    }

    /**
     * Catégories : priorité à la valeur enregistrée en base (onglet Config),
     * fallback sur Config/config.php au tout premier lancement.
     */
    protected function getStoredCategories()
    {
        $row = DB::table('ccm_events_settings')->where('key', 'categories')->first();

        return $row ? json_decode($row->value, true) : config('ccm_events.categories');
    }

    /**
     * Enregistre la bannière d'un évènement dans public/SPTheme/images/event/
     * et retourne le nom de fichier (stocké tel quel dans banner_path).
     */
    protected function storeEventBanner($eventId, $file)
    {
        $dir = public_path('SPTheme/images/event');
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        // Extension déduite du contenu réel du fichier (pas du nom envoyé par le navigateur)
        $ext = strtolower($file->extension() ?: $file->getClientOriginalExtension());
        if ($ext === 'jpeg') {
            $ext = 'jpg';
        }

        $filename = 'event_' . $eventId . '.' . $ext;

        // Grande image : on la réduit ; sinon (ou si GD indisponible) on la garde telle quelle
        if (!$this->resizeBanner($file->getRealPath(), $dir . '/' . $filename, $ext)) {
            $file->move($dir, $filename);
        }

        return $filename;
    }

    /**
     * Réduit une image trop large (max 1600 px de large, ratio conservé).
     * Retourne false si rien n'a été fait (image déjà assez petite, format
     * non géré comme GIF/BMP/AVIF, GD absent...) : l'appelant garde alors
     * le fichier d'origine.
     */
    protected function resizeBanner($src, $dest, $ext, $maxWidth = 1600)
    {
        if (!in_array($ext, ['jpg', 'png', 'webp'], true) || !function_exists('imagecreatefromstring')) {
            return false;
        }

        $info = @getimagesize($src);
        if (!$info || $info[0] <= $maxWidth) {
            return false;
        }
        // Évite de saturer la mémoire PHP sur une image gigantesque
        if ($info[0] * $info[1] > 40000000) {
            return false;
        }

        $img = @imagecreatefromstring(file_get_contents($src));
        if (!$img) {
            return false;
        }

        $newW = $maxWidth;
        $newH = (int) round($info[1] * $maxWidth / $info[0]);
        $dst  = imagecreatetruecolor($newW, $newH);

        if ($ext !== 'jpg') {
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
        }
        imagecopyresampled($dst, $img, 0, 0, 0, 0, $newW, $newH, $info[0], $info[1]);

        $ok = false;
        switch ($ext) {
            case 'jpg':  $ok = imagejpeg($dst, $dest, 85); break;
            case 'png':  $ok = imagepng($dst, $dest, 6); break;
            case 'webp': $ok = function_exists('imagewebp') && imagewebp($dst, $dest, 85); break;
        }

        imagedestroy($dst);
        imagedestroy($img);

        return $ok;
    }

    protected function deleteEventBanner($filename)
    {
        if (!$filename) {
            return;
        }
        $path = public_path('SPTheme/images/event/' . $filename);
        if (file_exists($path)) {
            @unlink($path);
        }
    }
}