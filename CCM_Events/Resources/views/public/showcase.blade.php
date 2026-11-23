@extends('app')
@section('title', 'Nos évènements')

@section('content')

@php
// Mêmes couleurs / icônes que la page pilotes (/events)
$categoryColors = [
    'formation'    => '#0d6efd',
    'rallye'       => '#fd7e14',
    'group_flight' => '#20c997',
    'challenge'    => '#6f42c1',
    'special'      => '#e83e8c',
];
$categoryIcons = [
    'formation'    => 'ph-graduation-cap',
    'rallye'       => 'ph-flag-checkered',
    'group_flight' => 'ph-users-three',
    'challenge'    => 'ph-trophy',
    'special'      => 'ph-star',
];

// Nettoyage de la description (mêmes règles que la page /events)
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

$discordUrl = 'https://discord.com/invite/sTxk7NXcjE';
// Noms des aéroports (table phpVMS "airports", clé = ICAO)
$ccmIcaos = collect([$upcoming->all(), $past->all()])->flatten(1)
    ->flatMap(function ($e) { return $e->legs->flatMap(function ($l) { return [$l->departure_icao, $l->arrival_icao]; }); })
    ->unique()->values()->all();
$ccmAirports = $ccmIcaos ? \App\Models\Airport::whereIn('id', $ccmIcaos)->pluck('name', 'id') : collect();

// Logos des réseaux
$ccmNetLogos = [
    'ivao'   => ['src' => public_asset('/SPTheme/images/ivao-small.png'),   'name' => 'IVAO'],
    'vatsim' => ['src' => public_asset('/SPTheme/images/vatsim-small.png'), 'name' => 'VATSIM'],
];


$stats = [
    ['value' => $totalEvents,         'label' => 'Évènement(s) organisé(s)',     'icon' => 'ph-calendar-check'],
    ['value' => $totalParticipations, 'label' => 'Participation(s) au total',    'icon' => 'ph-users-three'],
    ['value' => $average,             'label' => 'Participants en moyenne',      'icon' => 'ph-chart-line-up'],
];
@endphp

<style>
#ccm-show-wrap { padding: 10px 0; max-width: 1920px; margin: 0 auto; }
.ccm-ev-card { border-radius: 8px; overflow: hidden; }
.ccm-ev-icon { width: 56px; height: 56px; border-radius: 50%; display:flex; align-items:center; justify-content:center; margin: 0 auto 10px; font-size: 24px; color: #fff; }
.ccm-ev-date-pill { display:inline-flex; flex-direction:column; align-items:center; padding: 6px 14px; border-radius: 14px; background: var(--bs-tertiary-bg); margin-bottom: 10px; line-height: 1.3; }
#ccm-show-wrap .ccm-ev-date-main, #ccm-show-wrap .ccm-ev-date-main i { font-size: 15px !important; font-weight: 600; color: var(--bs-body-color) !important; }
#ccm-show-wrap .ccm-ev-date-sub { font-size: 13px !important; color: var(--bs-body-color) !important; opacity: .8; }
#ccm-show-wrap .ccm-ev-legs { font-family: monospace; background: var(--bs-tertiary-bg); border-radius: 8px; padding: 8px 16px; margin-bottom: 12px; display:flex; flex-wrap:wrap; justify-content:center; gap: 6px 14px; }
#ccm-show-wrap .ccm-leg, #ccm-show-wrap .ccm-leg b { font-size: 18px !important; font-weight: 700 !important; color: var(--bs-body-color) !important; }
#ccm-show-wrap .ccm-leg i { font-size: 20px !important; color: var(--bs-primary) !important; vertical-align: middle; }
#ccm-show-wrap .ccm-ev-count { display:inline-flex; align-items:center; gap:8px; background: var(--bs-primary); border-radius: 20px; padding: 6px 16px; }
#ccm-show-wrap .ccm-ev-count i, #ccm-show-wrap .ccm-ev-count b, #ccm-show-wrap .ccm-ev-count span { font-size: 15px !important; color: #fff !important; font-weight: 700 !important; }
.ccm-ev-desc, .ccm-ev-desc * { color: inherit !important; background: none !important; font-size: inherit !important; font-family: inherit !important; }
.ccm-ev-desc { font-size: 13px; text-align: left; }
.ccm-ev-desc p { margin: 0 0 6px; }
.ccm-ev-desc a { color: var(--bs-primary) !important; text-decoration: underline; }
.ccm-callsigns { display:flex; flex-wrap:wrap; justify-content:center; gap:6px; }
#ccm-show-wrap .ccm-callsign { font-family: monospace; font-size: 13px !important; padding: 4px 9px; border-radius: 6px; background: var(--bs-tertiary-bg); color: var(--bs-body-color) !important; }
#ccm-show-wrap .btn { font-size: 14px !important; padding: 7px 14px !important; }
.ccm-past-table td, .ccm-past-table th { vertical-align: middle; }
.ccm-past-table .ccm-callsigns { justify-content: flex-start; }

/* Trajets avec nom des aéroports */
.ccm-legs2 { width:100%; margin-bottom:12px; }
.ccm-leg2 { display:flex; align-items:center; justify-content:center; gap:10px; background: var(--bs-tertiary-bg); border-radius: 8px; padding: 8px 10px; margin-bottom: 6px; }
.ccm-leg2-apt { flex:1; min-width:0; line-height:1.25; text-align:center; }
.ccm-leg2 .ccm-leg2-icao { display:block; font-family:monospace; font-weight:700 !important; font-size:17px !important; color: var(--bs-body-color) !important; }
.ccm-leg2 .ccm-leg2-name { display:block; font-size:11px !important; opacity:.75; color: var(--bs-body-color) !important; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.ccm-leg2 .ccm-leg2-arrow { font-size:20px !important; font-weight:700; color: var(--bs-primary) !important; flex:0 0 auto; }
.ccm-past-table .ccm-leg2 { margin-bottom:4px; padding:5px 8px; min-width:230px; }
.ccm-net-row { display:inline-flex; align-items:center; gap:8px; background: var(--bs-tertiary-bg); border-radius: 20px; padding: 4px 12px; font-size:13px !important; font-weight:600; }
.ccm-net-row img { height:22px; width:auto; }
/* Historique compact */
.ccm-past-table td { padding-top:10px !important; padding-bottom:10px !important; }
.ccm-past-day { display:block; font-size:20px !important; font-weight:800; line-height:1; }
.ccm-past-month { display:block; font-size:12px !important; opacity:.7; text-transform:capitalize; }
.ccm-past-thumb { width:170px; height:90px; object-fit:contain; background: var(--bs-tertiary-bg); border-radius:6px; flex:0 0 auto; }
.ccm-past-thumb-empty { display:flex; align-items:center; justify-content:center; color:#fff; font-size:28px; }
.ccm-past-title { font-weight:700; font-size:14px !important; }
#ccm-show-wrap .ccm-past-route { display:flex; align-items:center; justify-content:center; gap:10px; padding:3px 0; }
#ccm-show-wrap .ccm-past-apt { width:150px; min-width:0; line-height:1.2; text-align:center; }
#ccm-show-wrap .ccm-past-apt b { display:block; font-family:monospace; font-size:14px !important; }
#ccm-show-wrap .ccm-past-apt small { display:block; font-size:11px !important; opacity:.7; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
#ccm-show-wrap .ccm-past-arrow { color: var(--bs-primary) !important; font-weight:700; font-size:16px !important; flex:0 0 auto; }
#ccm-show-wrap .ccm-past-count { display:inline-flex; align-items:center; gap:6px; background: var(--bs-primary); color:#fff !important; border-radius:20px; padding:4px 12px; font-weight:700; font-size:14px !important; }
#ccm-show-wrap .ccm-past-count i { color:#fff !important; }
</style>

{{-- Bannière --}}
<div class="row">
   <div class="col-12 mb-3">
      <div class="card border mb-0">
         <div class="card-body">
            <img src="{{ public_asset('/SPTheme/images/banner/evenements.jpg') }}" class="img-fluid card-img-top rounded mb-3" width="1920" height="200" alt="Évènements">
         </div>
      </div>
   </div>
</div>

<div class="row">
    <div class="col-12">
        <h5 class="mb-1"><i class="ph-fill ph-calendar-check align-middle me-1"></i> Les évènements d'Air Corsica Virtuel</h5>
        <p class="text-muted small mb-3">Vols de groupe, fly-in, formations… Découvrez nos prochains rendez-vous et l'historique de nos évènements.</p>
    </div>
</div>

{{-- Statistiques --}}
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

<div id="ccm-show-wrap">

  {{-- ══════════ ÉVÈNEMENTS À VENIR ══════════ --}}
  <h5 class="mb-3"><i class="ph-fill ph-airplane-takeoff align-middle me-1"></i> Prochains évènements</h5>
  <div class="row">
  @forelse($upcoming as $event)
    @php
      $isFlight = $event->isFlight();
      $catColor = $categoryColors[$event->category] ?? '#0d6efd';
      $catIcon  = $isFlight ? ($categoryIcons[$event->category] ?? 'ph-airplane-in-flight') : 'ph-confetti';

      $dayStr  = $event->date->format('Y-m-d');
      $startFr = \Carbon\Carbon::parse($dayStr.' '.$event->start_time, 'Europe/Paris');
      $endFr   = \Carbon\Carbon::parse($dayStr.' '.$event->end_time, 'Europe/Paris');

      $cleanDesc = $event->description ? ccmCleanDescription($event->description) : '';
      $count     = $event->pilot_callsigns->count();
    @endphp
    <div class="col-xxl-4 col-xl-4 col-lg-6 col-md-6 col-sm-12 mb-3">
      <div class="card border h-100 ccm-ev-card" style="border-top:3px solid {{ $catColor }};">
        @if($event->banner_path)
          <img src="{{ public_asset('/SPTheme/images/event/'.$event->banner_path) }}?v={{ optional($event->updated_at)->timestamp }}" class="w-100" style="display:block; height:auto; max-height:360px; object-fit:contain; background:var(--bs-tertiary-bg);" alt="{{ $event->title }}">
        @endif
        <div class="card-body text-center d-flex flex-column">

          @if(!$event->banner_path)
            <div class="ccm-ev-icon" style="background: {{ $catColor }};"><i class="ph-fill {{ $catIcon }}"></i></div>
          @endif

          <h5 class="mb-2">{{ $event->title }}</h5>

          <div class="d-flex flex-wrap justify-content-center align-items-center gap-2 mb-3">
            <span class="badge" style="background: {{ $catColor }}; color:#fff;">{{ $categories[$event->category] ?? $event->category }}</span>
            <div class="ccm-ev-count">
              <i class="ph-fill ph-users"></i>
              <span><b>{{ $count }}</b> inscrit{{ $count > 1 ? 's' : '' }}</span>
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

          <div class="ccm-ev-date-pill align-self-center">
            <span class="ccm-ev-date-main">
              <i class="ph-fill ph-clock"></i>
              {{ $event->date->format('d/m/Y') }} &middot; {{ $startFr->copy()->utc()->format('H:i') }}Z &ndash; {{ $endFr->copy()->utc()->format('H:i') }}Z
            </span>
            <span class="ccm-ev-date-sub">({{ $startFr->format('H:i') }} &ndash; {{ $endFr->format('H:i') }} heure française)</span>
          </div>

          @if($isFlight && $event->legs->count())
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

          {{-- Briefing : réservé aux pilotes connectés --}}
          @if($cleanDesc && auth()->check())
            <div class="ccm-ev-desc mb-3">{!! $cleanDesc !!}</div>
          @endif

          @if($count && auth()->check())
            <div class="ccm-callsigns mb-3">
              @foreach($event->pilot_callsigns as $cs)
                <span class="ccm-callsign">{{ $cs }}</span>
              @endforeach
            </div>
          @endif

          {{-- Bouton d'inscription : réservé aux pilotes connectés --}}
          @auth
          <div class="mt-auto">
            <a href="{{ url('/events') }}" class="btn btn-primary"><i class="ph-fill ph-check-circle align-middle me-1"></i>S'inscrire</a>
          </div>
          @endauth
        </div>
      </div>
    </div>
  @empty
    <div class="col-12 mb-3">
      <div class="card border"><div class="card-body text-center text-muted">Aucun évènement prévu pour le moment. Revenez bientôt !</div></div>
    </div>
  @endforelse
  </div>

  {{-- ══════════ HISTORIQUE ══════════ --}}
  <h5 class="mb-3 mt-2"><i class="ph-fill ph-clock-counter-clockwise align-middle me-1"></i> Historique des évènements</h5>
  <div class="card border">
    <div class="card-body p-0">
      @if($past->count())
      <div class="table-responsive">
        <table class="table table-hover mb-0 ccm-past-table">
          <thead>
            <tr>
              <th class="ps-3" style="width:110px;">Date</th>
              <th>Évènement</th>
              <th class="text-center" style="width:380px;">Trajet(s)</th>
              <th class="text-center" style="width:130px;">Participants</th>
              @auth<th>Pilotes</th>@endauth
            </tr>
          </thead>
          <tbody>
          @foreach($past as $event)
            @php $count = $event->pilot_callsigns->count(); @endphp
            <tr>
              <td class="ps-3 text-nowrap">
                <span class="ccm-past-day">{{ $event->date->format('d') }}</span>
                <span class="ccm-past-month">{{ $event->date->translatedFormat('M Y') }}</span>
              </td>
              <td>
                <div class="d-flex align-items-center gap-3">
                  @if($event->banner_path)
                    <img src="{{ public_asset('/SPTheme/images/event/'.$event->banner_path) }}?v={{ optional($event->updated_at)->timestamp }}" class="ccm-past-thumb" alt="{{ $event->title }}">
                  @else
                    <div class="ccm-past-thumb ccm-past-thumb-empty" style="background: {{ $categoryColors[$event->category] ?? '#0d6efd' }};">
                      <i class="ph-fill {{ $event->isFlight() ? 'ph-airplane-in-flight' : 'ph-confetti' }}"></i>
                    </div>
                  @endif
                  <div class="min-w-0">
                    <div class="ccm-past-title">{{ $event->title }}</div>
                    <div class="d-flex align-items-center gap-2 mt-1">
                      @if($event->category !== 'event')
                        <span class="badge" style="background: {{ $categoryColors[$event->category] ?? '#0d6efd' }}; color:#fff;">{{ $categories[$event->category] ?? $event->category }}</span>
                      @endif
                      @foreach($event->networks() as $net)
                        @if(isset($ccmNetLogos[$net]))<img src="{{ $ccmNetLogos[$net]['src'] }}" alt="{{ $ccmNetLogos[$net]['name'] }}" title="{{ $ccmNetLogos[$net]['name'] }}" style="height:18px;">@endif
                      @endforeach
                    </div>
                  </div>
                </div>
              </td>
              <td class="text-center">
                @if($event->isFlight() && $event->legs->count())
                  @foreach($event->legs as $leg)
                    <div class="ccm-past-route">
                      <div class="ccm-past-apt">
                        <b>{{ $leg->departure_icao }}</b>
                        <small title="{{ $ccmAirports[$leg->departure_icao] ?? '' }}">{{ $ccmAirports[$leg->departure_icao] ?? '' }}</small>
                      </div>
                      <span class="ccm-past-arrow">&rarr;</span>
                      <div class="ccm-past-apt">
                        <b>{{ $leg->arrival_icao }}</b>
                        <small title="{{ $ccmAirports[$leg->arrival_icao] ?? '' }}">{{ $ccmAirports[$leg->arrival_icao] ?? '' }}</small>
                      </div>
                    </div>
                  @endforeach
                @else
                  <span class="text-muted">—</span>
                @endif
              </td>
              <td class="text-center">
                <span class="ccm-past-count"><i class="ph-fill ph-users"></i> {{ $count }}</span>
              </td>
              @auth
              <td>
                @if($count)
                  <div class="ccm-callsigns">
                    @foreach($event->pilot_callsigns as $cs)
                      <span class="ccm-callsign">{{ $cs }}</span>
                    @endforeach
                  </div>
                @else
                  <span class="text-muted">—</span>
                @endif
              </td>
              @endauth
            </tr>
          @endforeach
          </tbody>
        </table>
      </div>
      @else
        <div class="p-3 text-center text-muted">Aucun évènement passé pour le moment.</div>
      @endif
    </div>
  </div>

</div>
@endsection