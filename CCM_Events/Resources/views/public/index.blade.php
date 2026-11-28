@extends('app')
@section('title', 'Évènements')

@section('content')

@php
// Couleurs fixes par catégorie (plus de crc32 : une nouvelle catégorie ne change plus les autres)
$categoryColors = [
    'formation'    => '#0d6efd',
    'rallye'       => '#fd7e14',
    'group_flight' => '#20c997',
    'challenge'    => '#6f42c1',
    'special'      => '#e83e8c',
];
$categoryIcons = [
    'formation'     => 'ph-graduation-cap',
    'rallye'        => 'ph-flag-checkered',
    'group_flight'  => 'ph-users-three',
    'challenge'     => 'ph-trophy',
    'special'       => 'ph-star',
];

// Nettoyage de la description : on garde uniquement une liste de balises, sans aucun attribut
// (sauf href http/https sur les liens). Supprime styles inline, onclick, scripts, etc.
if (!function_exists('ccmCleanDescription')) {
    function ccmCleanDescription($html) {
        $html = strip_tags((string) $html, '<p><br><strong><b><em><i><u><ul><ol><li><a>');
        $html = preg_replace_callback('/<a\b([^>]*)>/i', function ($m) {
            if (preg_match('/href\s*=\s*"(https?:\/\/[^"]*)"/i', $m[1], $h)) {
                return '<a href="' . e($h[1]) . '" target="_blank" rel="noopener noreferrer">';
            }
            return '<a>';
        }, $html);
        $html = preg_replace('/<(p|br|strong|b|em|i|u|ul|ol|li)\b[^>]*>/i', '<$1>', $html);
        return $html;
    }
}

$goingCount   = $events->where('my_participation', 'participating')->count();
$totalCount   = $events->count();
$pendingCount = $events->whereNull('my_participation')->count();
// Noms des aéroports (table phpVMS "airports", clé = ICAO)
$ccmIcaos = collect([$events->all()])->flatten(1)
    ->flatMap(function ($e) { return $e->legs->flatMap(function ($l) { return [$l->departure_icao, $l->arrival_icao]; }); })
    ->unique()->values()->all();
$ccmAirports = $ccmIcaos ? \App\Models\Airport::whereIn('id', $ccmIcaos)->pluck('name', 'id') : collect();

// Logos des réseaux
$ccmNetLogos = [
    'ivao'   => ['src' => public_asset('/SPTheme/images/ivao-small.png'),   'name' => 'IVAO'],
    'vatsim' => ['src' => public_asset('/SPTheme/images/vatsim-small.png'), 'name' => 'VATSIM'],
];


$stats = [
    ['value' => $totalCount,   'label' => 'Évènement(s)',          'icon' => 'ph-calendar-check'],
    ['value' => $goingCount,   'label' => 'Votre(s) participation(s)', 'icon' => 'ph-check-circle'],
    ['value' => $pendingCount, 'label' => 'En attente de réponse', 'icon' => 'ph-hourglass-medium'],
];
@endphp

<style>
#ccm-pub-wrap { padding: 20px 0; max-width: 1920px; margin: 0 auto; }

.ccm-ev-card { border-radius: 8px; overflow: hidden; transition: transform .15s, box-shadow .15s; }
.ccm-ev-card:hover { transform: translateY(-3px); box-shadow: 0 10px 24px rgba(0,0,0,.12); }
.ccm-ev-icon { width: 56px; height: 56px; border-radius: 50%; display:flex; align-items:center; justify-content:center; margin: 0 auto 10px; font-size: 24px; color: #fff; }

/* Date / heure */
.ccm-ev-date-pill { display:inline-flex; flex-direction:column; align-items:center; padding: 6px 14px; border-radius: 14px; background: var(--bs-tertiary-bg); margin-bottom: 10px; line-height: 1.3; }
.ccm-ev-date-main { font-size: 14px !important; font-weight: 600; display:flex; align-items:center; gap:6px; color: var(--bs-body-color) !important; }
.ccm-ev-date-sub { font-size: 12px !important; color: var(--bs-body-color) !important; opacity: .75; }

/* Trajets (legs) */
.ccm-ev-legs { font-family: monospace; font-size: 16px !important; font-weight: 700; color: var(--bs-body-color) !important; background: var(--bs-tertiary-bg); border-radius: 8px; padding: 8px 16px; margin-bottom: 12px; display:flex; flex-wrap:wrap; justify-content:center; align-items:center; gap: 6px 14px; }
.ccm-ev-legs i { font-size: 18px; color: var(--bs-primary); vertical-align: middle; }

/* Description : texte neutre, aligné à gauche, limité à 4 lignes */
.ccm-ev-desc, .ccm-ev-desc * { color: inherit !important; background: none !important; font-size: inherit !important; font-weight: inherit !important; font-family: inherit !important; }
.ccm-ev-desc strong, .ccm-ev-desc b { font-weight: 600 !important; }
.ccm-ev-desc a { color: var(--bs-primary) !important; text-decoration: underline; }
.ccm-ev-desc { font-size: 13px; color: var(--bs-secondary-color); text-align: left; }
.ccm-ev-desc p { margin: 0 0 6px; }
.ccm-ev-desc p:last-child { margin-bottom: 0; }
.ccm-ev-desc-clamp { display: -webkit-box; -webkit-line-clamp: 4; -webkit-box-orient: vertical; overflow: hidden; }

/* Boutons de la carte */
.ccm-ev-card .btn { font-size: 13px !important; padding: 6px 12px; }
.ccm-ev-card .badge { font-size: 12px; padding: 6px 10px; }

/* Renforts de spécificité : le thème écrase les tailles sur span/btn/badge */
#ccm-pub-wrap .ccm-ev-legs, #ccm-pub-wrap .ccm-ev-legs span { font-size: 16px !important; font-weight: 700 !important; color: var(--bs-body-color) !important; }
#ccm-pub-wrap .ccm-ev-legs i { font-size: 20px !important; color: var(--bs-primary) !important; }
#ccm-pub-wrap .ccm-ev-date-main, #ccm-pub-wrap .ccm-ev-date-main i { font-size: 15px !important; }
#ccm-pub-wrap .ccm-ev-date-sub { font-size: 13px !important; color: var(--bs-body-color) !important; opacity: .8; }
#ccm-pub-wrap .ccm-ev-card .btn { font-size: 14px !important; padding: 7px 14px !important; line-height: 1.3; }
#ccm-pub-wrap .ccm-ev-card .badge { font-size: 13px !important; padding: 7px 12px !important; }
#ccm-pub-wrap .ccm-ev-card .btn i, #ccm-pub-wrap .ccm-ev-card .badge i { font-size: 15px !important; }

#ccm-pub-wrap .ccm-leg, #ccm-pub-wrap .ccm-leg b { font-size: 20px !important; font-weight: 700 !important; letter-spacing: .5px; color: var(--bs-body-color) !important; line-height: 1.2; }
#ccm-pub-wrap .ccm-leg i { font-size: 22px !important; color: var(--bs-primary) !important; vertical-align: middle; }

#ccm-pub-wrap .ccm-ev-count { display:inline-flex; align-items:center; gap:8px; background: var(--bs-primary); border-radius: 20px; padding: 6px 16px; box-shadow: 0 2px 8px rgba(13,110,253,.35); }
#ccm-pub-wrap .ccm-ev-count i { font-size: 20px !important; color: #fff !important; }
#ccm-pub-wrap .ccm-count-text, #ccm-pub-wrap .ccm-count-text b { font-size: 15px !important; font-weight: 700 !important; color: #fff !important; line-height: 1.2; }

/* Modales */
.ccm-modal { display:none; position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,.5); z-index:9999; align-items:center; justify-content:center; padding: 12px; }
.ccm-modal-box { background: var(--bs-body-bg); color: var(--bs-body-color); border-radius:8px; padding:24px; width: min(460px, 100%); max-height:85vh; overflow-y:auto; }
.ccm-modal-box.ccm-wide { width: min(960px, 100%); max-height: 90vh; padding: 32px; }
.ccm-modal-box.ccm-wide .ccm-ev-desc, .ccm-modal-box.ccm-wide .ccm-ev-desc * { font-size: 15px !important; line-height: 1.6; }
.ccm-modal-box.ccm-wide .ccm-ev-desc p { margin: 0 0 10px; }
.ccm-modal-box select.form-control { background-color: var(--bs-body-bg); color: var(--bs-body-color); border-color: var(--bs-border-color); }
html[data-bs-theme="dark"] .ccm-modal-box select.form-control { color-scheme: dark; }
html[data-bs-theme="light"] .ccm-modal-box select.form-control { color-scheme: light; }

/* Trajets avec nom des aéroports */
.ccm-legs2 { width:100%; margin-bottom:12px; }
.ccm-leg2 { display:flex; align-items:center; justify-content:center; gap:10px; background: var(--bs-tertiary-bg); border-radius: 8px; padding: 8px 10px; margin-bottom: 6px; }
.ccm-leg2-apt { flex:1; min-width:0; line-height:1.25; text-align:center; }
.ccm-leg2 .ccm-leg2-icao { display:block; font-family:monospace; font-weight:700 !important; font-size:17px !important; color: var(--bs-body-color) !important; }
.ccm-leg2 .ccm-leg2-name { display:block; font-size:11px !important; opacity:.75; color: var(--bs-body-color) !important; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.ccm-leg2 .ccm-leg2-arrow { font-size:20px !important; font-weight:700; color: var(--bs-primary) !important; flex:0 0 auto; }
.ccm-net-row { display:inline-flex; align-items:center; gap:8px; background: var(--bs-tertiary-bg); border-radius: 20px; padding: 4px 12px; font-size:13px !important; font-weight:600; }
.ccm-net-row img { height:22px; width:auto; }
</style>

<div class="row">
   <div class="col-xxl-12 col-xl-12 col-lg-12 col-md-12 col-sm-12 mb-3">
      <div class="card border mb-0">
         <div class="card-body">
            <img src="{{ public_asset('/SPTheme/images/banner/evenements.jpg') }}" class="img-fluid card-img-top rounded mb-3" width="1920" height="200" alt="@lang('sptheme.banner')">
         </div>
      </div>
   </div>
</div>

<div class="row">
    <div class="col-xxl-12 col-xl-12">
        <h5 class="mb-1"><i class="ph-fill ph-calendar-check align-middle me-1"></i> Retrouvez tous nos évènements</h5>
        <p class="text-muted small mb-3">Inscrivez-vous, réservez votre avion et participez à la vie de la compagnie.</p>
    </div>
</div>

<div class="row">
    @foreach($stats as $stat)
    <div class="col-xxl-4 col-xl-4 col-lg-4 col-md-6 col-sm-12 mb-3">
        <div class="card border">
            <div class="card-body widget-desk">
                <div class="text-end">
                    <h4 class="mt-0 mb-0 fw-bold">{{ $stat['value'] }}</h4>
                    <p class="mb-0">{{ $stat['label'] }}</p>
                </div>
                <div class="widget-icon"><i class="ph-fill {{ $stat['icon'] }}"></i></div>
                <div class="clearfix"></div>
            </div>
        </div>
    </div>
    @endforeach
</div>

<div id="ccm-pub-wrap">
  <div class="row">
  @forelse($events as $event)
    @php
      $isFlight = $event->isFlight();
      $catColor = $categoryColors[$event->category] ?? '#0d6efd';
      $catIcon  = $isFlight
          ? ($categoryIcons[$event->category] ?? 'ph-airplane-in-flight')
          : 'ph-confetti';

      // Heures saisies en heure française (Europe/Paris) -> conversion en Zulu pour l'affichage
      $dayStr  = $event->date->format('Y-m-d');
      $startFr = \Carbon\Carbon::parse($dayStr.' '.$event->start_time, 'Europe/Paris');
      $endFr   = \Carbon\Carbon::parse($dayStr.' '.$event->end_time, 'Europe/Paris');
      $startZ  = $startFr->copy()->utc()->format('H:i');
      $endZ    = $endFr->copy()->utc()->format('H:i');

      $cleanDesc = $event->description ? ccmCleanDescription($event->description) : '';
    @endphp
    <div class="col-xxl-4 col-xl-4 col-lg-6 col-md-6 col-sm-12 mb-3" data-event-id="{{ $event->id }}">
      <div class="card border h-100 ccm-ev-card" style="border-top:3px solid {{ $catColor }};">
        @if($event->banner_path)
          <img src="{{ public_asset('/SPTheme/images/event/'.$event->banner_path) }}?v={{ optional($event->updated_at)->timestamp }}" class="w-100" style="display:block; height:auto; max-height:360px; object-fit:contain; background:var(--bs-tertiary-bg);" alt="{{ $event->title }}">
        @endif
        <div class="card-body text-center d-flex flex-column">

          @if(!$event->banner_path)
            <div class="ccm-ev-icon" style="background: {{ $catColor }};">
              <i class="ph-fill {{ $catIcon }}"></i>
            </div>
          @endif

          <h5 class="mb-1">{{ $event->title }}</h5>
          <div class="d-flex flex-wrap justify-content-center align-items-center gap-2 mb-3">
            <span class="badge" style="background: {{ $catColor }}; color:#fff;">
              {{ $categories[$event->category] ?? $event->category }}
            </span>
            {{-- Nombre de participants (valeur serveur si dispo, sinon chargée en JS) --}}
            <div class="ccm-ev-count" id="ccm-count-{{ $event->id }}" data-event="{{ $event->id }}" @if(!isset($event->participants_count)) data-fetch="1" @endif>
              <i class="ph-fill ph-users"></i>
              <div class="ccm-count-text">@if(isset($event->participants_count))<b>{{ $event->participants_count }}</b> participant{{ $event->participants_count > 1 ? 's' : '' }}@else ... @endif</div>
            </div>
          </div>

          @if(count($event->networks()))
            <div class="mb-3">
              <span class="ccm-net-row">
                Réseau :
                @foreach($event->networks() as $net)
                  @if(isset($ccmNetLogos[$net]))<img src="{{ $ccmNetLogos[$net]['src'] }}" alt="{{ $ccmNetLogos[$net]['name'] }}" title="{{ $ccmNetLogos[$net]['name'] }}">@endif
                @endforeach
              </span>
            </div>
          @endif

          {{-- Date + heure : Zulu en principal, heure française en petit --}}
          <div class="ccm-ev-date-pill align-self-center">
            <span class="ccm-ev-date-main">
              <i class="ph-fill ph-clock"></i>
              {{ $event->date->format('d/m/Y') }} &middot; {{ $startZ }}Z &ndash; {{ $endZ }}Z
            </span>
            <span class="ccm-ev-date-sub">({{ $startFr->format('H:i') }} &ndash; {{ $endFr->format('H:i') }} heure française)</span>
          </div>

          {{-- Trajets : juste sous la date --}}
          @if($isFlight)
          <div class="ccm-legs2">
            @foreach($event->legs as $leg)
              <div class="ccm-leg2">
                <div class="ccm-leg2-apt">
                  <span class="ccm-leg2-icao">{{ $leg->departure_icao }}</span>
                  <span class="ccm-leg2-name" title="{{ $ccmAirports[$leg->departure_icao] ?? '' }}">{{ $ccmAirports[$leg->departure_icao] ?? '' }}</span>
                </div>
                <span class="ccm-leg2-arrow">&rarr;</span>
                <div class="ccm-leg2-apt">
                  <span class="ccm-leg2-icao">{{ $leg->arrival_icao }}</span>
                  <span class="ccm-leg2-name" title="{{ $ccmAirports[$leg->arrival_icao] ?? '' }}">{{ $ccmAirports[$leg->arrival_icao] ?? '' }}</span>
                </div>
              </div>
            @endforeach
          </div>
          @endif

          @if($event->my_participation === 'participating' && !$event->my_reservation)
            <span class="badge bg-success mb-2 align-self-center"><i class="ph-fill ph-check-circle align-middle me-1"></i>J'y participe</span>
          @elseif($event->my_participation === 'not_participating')
            <span class="badge bg-secondary mb-2 align-self-center">Décliné</span>
          @endif

          {{-- Description : 4 lignes max + bouton Détails --}}
          @if($cleanDesc)
            <div class="ccm-ev-desc ccm-ev-desc-clamp mb-1">{!! $cleanDesc !!}</div>
            <template id="ccm-desc-{{ $event->id }}" data-title="{{ $event->title }}">{!! $cleanDesc !!}</template>
            <div class="mb-2">
              <button type="button" class="btn btn-outline-primary btn-sm px-3" onclick="ccmShowDetails({{ $event->id }})"><i class="ph-fill ph-info align-middle me-1"></i>Détails</button>
            </div>
          @endif

          @if($isFlight)
          <div id="ccm-flight-block-{{ $event->id }}">
            @if($event->my_reservation && $event->my_reservation->flight_active)
              @php
                $legsToShow = !empty($event->my_leg_ids)
                  ? $event->legs->whereIn('id', $event->my_leg_ids)
                  : $event->legs;
              @endphp
              <div class="alert alert-primary text-start p-2 mb-2">
                <strong class="d-block small mb-1"><i class="ph-fill ph-check-circle align-middle me-1"></i>Votre vol est activé !</strong>
                <p class="small mb-2">Avion positionné à {{ $event->my_reservation->airport_icao }}.</p>
                @forelse($legsToShow as $leg)
                  <div class="d-flex align-items-center justify-content-between gap-2 py-1 border-top">
                    <span class="small font-monospace">Leg {{ $leg->leg_number }} : {{ $leg->departure_icao }} → {{ $leg->arrival_icao }}</span>
                    <a class="btn btn-sm btn-primary" href="{{ url('/events') }}/{{ $event->id }}/freeflight/{{ $leg->id }}">Planifier</a>
                  </div>
                @empty
                  <p class="small text-muted mb-0">Aucune leg sélectionnée pour votre participation.</p>
                @endforelse
              </div>
            @endif
          </div>
          @endif

          <div class="mt-auto pt-2 d-flex flex-column gap-2">
            @if(!$isFlight)
              {{-- Évènement hors vol : un simple clic, sans aéroport ni avion --}}
              @if($event->my_participation !== 'participating')
                <button class="btn btn-primary btn-sm w-100" onclick="ccmParticipate({{ $event->id }})">
                  <i class="ph-fill ph-check align-middle me-1"></i> Je participe
                </button>
              @endif
            @elseif($event->my_reservation)
              <span class="badge bg-success"><i class="ph-fill ph-map-pin align-middle me-1"></i>Réservé : {{ $event->my_reservation->airport_icao }}@if($event->my_reservation->stand) · {{ $event->my_reservation->stand }} @endif</span>
              {{-- Modifier : masqué si l'avion a déjà été positionné par le staff (vol activé) --}}
              @if(!$event->my_reservation->original_airport_id)
                <button class="btn btn-sm btn-outline-primary"
                        data-icao="{{ $event->my_reservation->airport_icao }}"
                        data-stand="{{ $event->my_reservation->stand }}"
                        data-aircraft="{{ $event->my_reservation->aircraft_id }}"
                        onclick="ccmEditReservation(this, {{ $event->id }})">
                  <i class="ph-fill ph-pencil-simple align-middle me-1"></i> Modifier ma réservation
                </button>
              @endif
              <button class="btn btn-sm btn-outline-secondary" onclick="ccmCancelReservation({{ $event->my_reservation->id }})">Annuler ma réservation</button>
            @else
              <button class="btn btn-primary btn-sm w-100" onclick="ccmOpenReservationModal({{ $event->id }})">
                <i class="ph-fill ph-check align-middle me-1"></i> Je participe
              </button>
            @endif
            <div class="d-flex gap-2 justify-content-center">
              @if($event->my_participation !== 'not_participating')
                <button class="btn btn-sm btn-outline-secondary" onclick="ccmDecline({{ $event->id }})">Je décline</button>
              @endif
              <button class="btn btn-sm btn-outline-secondary" onclick="ccmShowParticipants({{ $event->id }})">Participants</button>
            </div>
          </div>

        </div>
      </div>
    </div>
  @empty
    <div class="col-12">
      <div class="alert alert-info" role="alert">
        <i class="ph-fill ph-info align-middle fs-4 me-1"></i> Aucun évènement en cours ou à venir.
      </div>
    </div>
  @endforelse
  </div>

  {{-- ── Modale Détails ── --}}
  <div class="ccm-modal" id="ccm-details-modal">
    <div class="ccm-modal-box ccm-wide">
      <h4 id="ccm-details-title"></h4>
      <div class="ccm-ev-desc" id="ccm-details-content"></div>
      <div style="text-align:right; margin-top:16px;">
        <button class="btn btn-default" onclick="ccmCloseModal('ccm-details-modal')">Fermer</button>
      </div>
    </div>
  </div>

  {{-- ── Modale Participants ── --}}
  <div class="ccm-modal" id="ccm-participants-modal">
    <div class="ccm-modal-box">
      <h4>Participants</h4>
      <div id="ccm-participants-list">Chargement...</div>
      <div style="text-align:right; margin-top:16px;">
        <button class="btn btn-default" onclick="ccmCloseModal('ccm-participants-modal')">Fermer</button>
      </div>
    </div>
  </div>

  {{-- ── Modale Réservation ── --}}
  <div class="ccm-modal" id="ccm-resa-modal">
    <div class="ccm-modal-box">
      <h4 id="ccm-resa-title">Je participe — Réserver mon avion</h4>
      <input type="hidden" id="ccm-resa-event-id" value="">

      <div class="form-group" id="ccm-resa-legs-group" style="display:none;">
        <label>Sur quelle(s) leg(s) participez-vous ? <span class="text-danger">*</span></label>
        <div id="ccm-resa-legs-list"></div>
      </div>

      <div class="form-group">
        <label>Aéroport (code ICAO) <span class="text-danger">*</span></label>
        <input type="text" class="form-control" id="ccm-resa-airport" list="ccm-resa-airport-list"
               placeholder="Ex : LFKJ" maxlength="4" style="text-transform:uppercase;"
               oninput="ccmLoadStandsDebounced()">
        <datalist id="ccm-resa-airport-list">
          @foreach($airports as $icao => $stands)
            <option value="{{ $icao }}">
          @endforeach
        </datalist>
      </div>

      <div class="form-group" id="ccm-resa-stand-group">
        <label>Emplacement (stand) <span class="text-danger">*</span></label>
        <select class="form-control" id="ccm-resa-stand">
          <option value="">-- Choisir un aéroport d'abord --</option>
        </select>
      </div>

      <div class="form-group">
        <label>Mon avion <span class="text-danger">*</span></label>
        <select class="form-control" id="ccm-resa-aircraft">
          <option value="">Chargement...</option>
        </select>
      </div>

      <div id="ccm-resa-error" class="text-danger small" style="display:none; margin-top:8px;"></div>

      <div style="text-align:right; margin-top:16px;">
        <button class="btn btn-default" onclick="ccmCloseModal('ccm-resa-modal')">Annuler</button>
        <button class="btn btn-primary" id="ccm-resa-submit" onclick="ccmSubmitReservation()">Réserver</button>
      </div>
    </div>
  </div>

</div>

@endsection

@section('scripts')
@parent
<script>
const CCM_BASE = @json(url('/events'));

// Échappe le HTML avant de l'injecter dans innerHTML
const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));

// Accept: application/json -> Laravel répond en JSON (et non en redirection HTML) aux erreurs de validation
function csrfHeaders() {
  return {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
  };
}

// fetch -> JSON, avec vérification du statut HTTP
// En cas d'erreur, on remonte le message envoyé par le serveur s'il existe
function ccmFetchJson(url, options) {
  return fetch(url, options).then(r =>
    r.json().catch(() => ({})).then(data => {
      if (!r.ok) throw new Error(data.message || ('HTTP ' + r.status));
      return data;
    })
  );
}

// Évite les doubles clics : désactive tous les boutons pendant une requête
let ccmBusy = false;
function ccmSetBusy(on) {
  ccmBusy = on;
  document.querySelectorAll('#ccm-pub-wrap .btn').forEach(b => { b.disabled = on; });
}
function ccmAction(url, onSuccess) {
  if (ccmBusy) return;
  ccmSetBusy(true);
  ccmFetchJson(url, { method: 'POST', headers: csrfHeaders() })
    .then(data => {
      if (data.success) { onSuccess ? onSuccess(data) : window.location.reload(); }
      else { alert(data.message || 'Une erreur est survenue.'); ccmSetBusy(false); }
    })
    .catch(() => { alert('Erreur de communication avec le serveur.'); ccmSetBusy(false); });
}

// ── Nombre de participants ──
function ccmSetCount(eventId, n) {
  const box = document.querySelector(`#ccm-count-${eventId} .ccm-count-text`);
  if (box) box.innerHTML = `<b>${n}</b> participant${n > 1 ? 's' : ''}`;
}
document.querySelectorAll('.ccm-ev-count[data-fetch]').forEach(el => {
  const id = el.dataset.event;
  ccmFetchJson(`${CCM_BASE}/${id}/participants`)
    .then(data => ccmSetCount(id, (data.participants || []).length))
    .catch(() => { el.style.display = 'none'; });
});

// ── Modales ──
function ccmCloseModal(id) { document.getElementById(id).style.display = 'none'; }
function ccmOpenModal(id) { document.getElementById(id).style.display = 'flex'; }

// Fermeture avec Échap ou clic en dehors de la boîte
document.addEventListener('keydown', e => {
  if (e.key === 'Escape') document.querySelectorAll('.ccm-modal').forEach(m => m.style.display = 'none');
});
document.querySelectorAll('.ccm-modal').forEach(m => {
  m.addEventListener('mousedown', e => { if (e.target === m) m.style.display = 'none'; });
});

// ── Détails ──
function ccmShowDetails(eventId) {
  const tpl = document.getElementById('ccm-desc-' + eventId);
  if (!tpl) return;
  document.getElementById('ccm-details-title').textContent = tpl.dataset.title || '';
  document.getElementById('ccm-details-content').innerHTML = tpl.innerHTML;
  ccmOpenModal('ccm-details-modal');
}

// ── Participation ──
function ccmParticipate(eventId) {
  ccmAction(`${CCM_BASE}/${eventId}/participate`);
}

function ccmDecline(eventId) {
  ccmAction(`${CCM_BASE}/${eventId}/decline`);
}

function ccmShowParticipants(eventId) {
  ccmOpenModal('ccm-participants-modal');
  const box = document.getElementById('ccm-participants-list');
  box.innerHTML = 'Chargement...';
  ccmFetchJson(`${CCM_BASE}/${eventId}/participants`)
    .then(data => {
      const list = data.participants.map(p => {
        const details = [];
        if (p.reserved_airport) details.push(`✈ ${esc(p.reserved_airport)}${p.reserved_stand ? ' · ' + esc(p.reserved_stand) : ''}`);
        if (p.reserved_aircraft) details.push(esc(p.reserved_aircraft));
        const detailsHtml = details.length ? `<div style="font-size:11px; color:var(--bs-secondary-color);">${details.join(' &middot; ')}</div>` : '';
        return `<div style="padding:6px 0; border-bottom:1px solid var(--bs-border-color);"><strong>${esc(p.user_callsign)}</strong>${detailsHtml}</div>`;
      }).join('') || '<p class="text-muted">Aucun participant.</p>';

      // Pilotes ayant décliné
      const decliners = data.decliners || [];
      const declinedHtml = decliners.length
        ? `<h5 style="margin-top:18px;">Ne participent pas <span class="badge bg-secondary">${decliners.length}</span></h5>` +
          decliners.map(d => `<div style="padding:4px 0; border-bottom:1px solid var(--bs-border-color); opacity:.75;">${esc(d.user_callsign)}</div>`).join('')
        : '';

      box.innerHTML = list + declinedHtml;
      ccmSetCount(eventId, data.participants.length);
    })
    .catch(() => { box.innerHTML = '<p class="text-danger">Erreur de chargement.</p>'; });
}

// ── Réservation ──
// ccmEdit = null en création, ou { icao, stand, aircraft } en modification
let ccmEdit = null;

function ccmEditReservation(btn, eventId) {
  ccmOpenReservationModal(eventId, {
    icao: (btn.dataset.icao || '').toUpperCase(),
    stand: btn.dataset.stand || '',
    aircraft: btn.dataset.aircraft || '',
  });
}

function ccmOpenReservationModal(eventId, edit = null) {
  ccmEdit = edit;
  document.getElementById('ccm-resa-title').textContent = edit ? 'Modifier ma réservation' : 'Je participe — Réserver mon avion';
  document.getElementById('ccm-resa-submit').textContent = edit ? 'Enregistrer' : 'Réserver';
  document.getElementById('ccm-resa-event-id').value = eventId;
  document.getElementById('ccm-resa-airport').value = edit ? edit.icao : '';
  document.getElementById('ccm-resa-stand').innerHTML = '<option value="">-- Choisir un aéroport d\'abord --</option>';
  document.getElementById('ccm-resa-stand-group').style.display = 'block';
  ccmStandsRequired = false;
  document.getElementById('ccm-resa-error').style.display = 'none';
  ccmLoadMyAircraft(eventId);
  ccmLoadLegsForReservation(eventId);
  if (edit) ccmLoadStands(); // stands de l'aéroport déjà réservé, avec le sien présélectionné
  ccmOpenModal('ccm-resa-modal');
}

function ccmLoadLegsForReservation(eventId) {
  const group = document.getElementById('ccm-resa-legs-group');
  const list = document.getElementById('ccm-resa-legs-list');
  group.style.display = 'none';
  list.innerHTML = '';

  ccmFetchJson(`${CCM_BASE}/${eventId}/legs`)
    .then(data => {
      if (!data.success || !data.legs.length) return;
      return ccmFetchJson(`${CCM_BASE}/${eventId}/leg-participants`).then(partData => {
        const myLegs = (partData.legs || []).map(id => String(id));
        group.style.display = 'block';
        // Un seul leg : coché d'office
        const seul = data.legs.length === 1;
        list.innerHTML = data.legs.map(leg => `
          <label style="display:flex; align-items:center; gap:8px; padding:6px 0;">
            <input type="checkbox" class="ccm-resa-leg-cb" value="${esc(leg.id)}" data-dep="${esc(leg.departure_icao)}" onchange="ccmSyncAirportFromLegs()" ${(seul || myLegs.includes(String(leg.id))) ? 'checked' : ''}>
            <span style="font-family:monospace; font-size:13px;">Leg ${esc(leg.leg_number)} : ${esc(leg.departure_icao)} → ${esc(leg.arrival_icao)}</span>
          </label>
        `).join('');

        // Aéroport pré-rempli avec le départ du leg 1
        // (en modification, on garde l'aéroport déjà réservé)
        ccmLegOneDep = data.legs[0].departure_icao || '';
        if (!ccmEdit) ccmSyncAirportFromLegs();
      });
    })
    .catch(() => {});
}

// Aéroport = départ du premier leg coché (par défaut : départ du leg 1)
let ccmLegOneDep = '';
function ccmSyncAirportFromLegs() {
  const first = document.querySelector('.ccm-resa-leg-cb:checked');
  const icao = (first ? first.dataset.dep : ccmLegOneDep) || '';
  const input = document.getElementById('ccm-resa-airport');
  if (icao && input.value.trim().toUpperCase() !== icao.toUpperCase()) {
    input.value = icao;
    ccmLoadStands();
  }
}

// Recherche des stands : anti-rebond + on ignore les réponses obsolètes
let ccmStandTimer = null;
let ccmStandReq = 0;
let ccmStandsRequired = false; // true si l'aéroport choisi a des stands paramétrés
function ccmLoadStandsDebounced() {
  clearTimeout(ccmStandTimer);
  ccmStandTimer = setTimeout(ccmLoadStands, 300);
}
function ccmLoadStands() {
  const airportInput = document.getElementById('ccm-resa-airport');
  const icao = airportInput.value.trim().toUpperCase();
  airportInput.value = icao;
  const eventId = document.getElementById('ccm-resa-event-id').value;
  const standSel = document.getElementById('ccm-resa-stand');
  const standGroup = document.getElementById('ccm-resa-stand-group');
  standGroup.style.display = 'block';
  ccmStandsRequired = false;
  if (icao.length !== 4) { standSel.innerHTML = '<option value="">-- Choisir un aéroport d\'abord --</option>'; return; }
  standSel.innerHTML = '<option value="">Chargement...</option>';
  const myReq = ++ccmStandReq;
  ccmFetchJson(`${CCM_BASE}/stands/${encodeURIComponent(icao)}?event_id=${encodeURIComponent(eventId)}`)
    .then(data => {
      if (myReq !== ccmStandReq) return; // une réponse plus récente est arrivée
      const stands = data.stands || [];
      // Aucun stand paramétré pour cet aéroport : on masque le champ, pas obligatoire
      if (!stands.length) {
        standSel.innerHTML = '<option value=""></option>';
        standGroup.style.display = 'none';
        ccmStandsRequired = false;
        return;
      }
      ccmStandsRequired = true;
      // En modification, mon propre stand n'est pas "pris" : il reste sélectionnable
      const isMine = s => ccmEdit && icao === ccmEdit.icao && String(s) === String(ccmEdit.stand);
      const reserved = (data.reserved || []).map(String).filter(s => !isMine(s));
      const libres = stands.filter(s => !reserved.includes(String(s))).length;
      // Les stands déjà réservés par un autre pilote sont grisés et non sélectionnables
      standSel.innerHTML = `<option value="">${libres ? '-- Choisir un stand --' : '-- Tous les stands sont pris --'}</option>` +
        stands.map(s => {
          const pris = reserved.includes(String(s));
          return `<option value="${esc(s)}" ${pris ? 'disabled' : ''} ${isMine(s) ? 'selected' : ''}>${esc(s)}${pris ? ' (réservé)' : ''}</option>`;
        }).join('');
    })
    .catch(() => { if (myReq === ccmStandReq) standSel.innerHTML = '<option value="">Erreur de chargement</option>'; });
}

function ccmLoadMyAircraft(eventId) {
  const aircraftSel = document.getElementById('ccm-resa-aircraft');
  aircraftSel.innerHTML = '<option value="">Chargement...</option>';
  ccmFetchJson(`${CCM_BASE}/${eventId}/my-aircraft`)
    .then(data => {
      aircraftSel.innerHTML = '<option value="">-- Choisir --</option>' +
        data.aircraft.map(a => `<option value="${esc(a.id)}">${esc(a.icao)} | ${esc(a.registration)}${a.name ? ' - ' + esc(a.name) : ''}</option>`).join('');
      // En modification : avion déjà réservé présélectionné
      if (ccmEdit && ccmEdit.aircraft) aircraftSel.value = String(ccmEdit.aircraft);
    })
    .catch(() => { aircraftSel.innerHTML = '<option value="">Erreur de chargement</option>'; });
}

function ccmShowResaError(msg) {
  const box = document.getElementById('ccm-resa-error');
  box.textContent = msg;
  box.style.display = 'block';
}

function ccmSubmitReservation() {
  if (ccmBusy) return;
  const eventId = document.getElementById('ccm-resa-event-id').value;
  const checkedLegs = Array.from(document.querySelectorAll('.ccm-resa-leg-cb:checked')).map(cb => parseInt(cb.value));
  const payload = {
    airport_icao: document.getElementById('ccm-resa-airport').value,
    stand: document.getElementById('ccm-resa-stand').value || null,
    aircraft_id: document.getElementById('ccm-resa-aircraft').value,
    legs: checkedLegs,
  };
  // Tous les champs sont obligatoires (le stand seulement s'il y en a de paramétrés)
  const hasLegs = document.querySelectorAll('.ccm-resa-leg-cb').length > 0;
  if (hasLegs && !checkedLegs.length) { ccmShowResaError('Vous devez cocher au moins une leg.'); return; }
  if (!payload.airport_icao || payload.airport_icao.length !== 4) { ccmShowResaError('L\'aéroport est obligatoire.'); return; }
  if (ccmStandsRequired && !payload.stand) { ccmShowResaError('Vous devez choisir un emplacement (stand).'); return; }
  if (!payload.aircraft_id) { ccmShowResaError('Vous devez choisir un avion.'); return; }

  document.getElementById('ccm-resa-error').style.display = 'none';
  ccmSetBusy(true);

  // La participation est enregistrée par le contrôleur, uniquement si la réservation réussit
  ccmFetchJson(`${CCM_BASE}/${eventId}/reservations`, { method: 'POST', headers: csrfHeaders(), body: JSON.stringify(payload) })
    .then(data => {
      if (!data.success) throw new Error(data.message || 'Erreur lors de la réservation.');
      window.location.reload();
    })
    .catch(err => {
      ccmSetBusy(false);
      ccmShowResaError(err.message || 'Erreur de communication avec le serveur.');
      // On recharge la liste : le stand qui vient d'être pris par un autre pilote apparaît grisé
      ccmLoadStands();
    });
}

function ccmCancelReservation(reservationId) {
  if (!confirm('Annuler votre réservation ?')) return;
  ccmAction(`${CCM_BASE}/reservations/${reservationId}/cancel`);
}
</script>
@endsection