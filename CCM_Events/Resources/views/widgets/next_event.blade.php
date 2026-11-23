{{--
    Widget "Prochain évènement" (page d'accueil, colonne de droite).
    Inclusion :  @include('ccm_events::widgets.next_event')
    N'affiche rien s'il n'y a aucun évènement à venir.
--}}
@php
    $ccmNow  = \Carbon\Carbon::now();
    $ccmNext = \Modules\CCM_Events\Entities\Event::with('legs')
        ->withCount(['participants as going_count' => function ($q) {
            $q->where('status', 'participating');
        }])
        ->where('status', 'active')
        ->where(function ($q) use ($ccmNow) {
            $q->where('date', '>', $ccmNow->format('Y-m-d'))
              ->orWhere(function ($q2) use ($ccmNow) {
                  $q2->where('date', $ccmNow->format('Y-m-d'))
                     ->where('end_time', '>=', $ccmNow->format('H:i:s'));
              });
        })
        ->orderBy('date')
        ->orderBy('start_time')
        ->first();
@endphp

@if($ccmNext)
    @php
        $ccmColors = [
            'formation'    => '#0d6efd',
            'rallye'       => '#fd7e14',
            'group_flight' => '#20c997',
            'challenge'    => '#6f42c1',
            'special'      => '#e83e8c',
        ];
        $ccmColor = $ccmColors[$ccmNext->category] ?? '#0d6efd';

        $ccmCatRow  = \Illuminate\Support\Facades\DB::table('ccm_events_settings')->where('key', 'categories')->first();
        $ccmCats    = $ccmCatRow ? json_decode($ccmCatRow->value, true) : config('ccm_events.categories');
        $ccmCatName = $ccmCats[$ccmNext->category] ?? $ccmNext->category;

        // Noms des aéroports (table phpVMS "airports", clé = ICAO)
        $ccmIcaos = $ccmNext->legs->flatMap(function ($l) { return [$l->departure_icao, $l->arrival_icao]; })->unique()->values()->all();
        $ccmAirports = $ccmIcaos ? \App\Models\Airport::whereIn('id', $ccmIcaos)->pluck('name', 'id') : collect();


        // Logos des réseaux
        $ccmNetLogos = [
            'ivao'   => ['src' => public_asset('/SPTheme/images/ivao-small.png'),   'name' => 'IVAO'],
            'vatsim' => ['src' => public_asset('/SPTheme/images/vatsim-small.png'), 'name' => 'VATSIM'],
        ];

        // Heures saisies en heure française
        $ccmDay   = $ccmNext->date->format('Y-m-d');
        $ccmStart = \Carbon\Carbon::parse($ccmDay.' '.$ccmNext->start_time, 'Europe/Paris');
        $ccmEnd   = \Carbon\Carbon::parse($ccmDay.' '.$ccmNext->end_time, 'Europe/Paris');
        $ccmNowFr = \Carbon\Carbon::now('Europe/Paris');

        // Compte à rebours
        if ($ccmNowFr->between($ccmStart, $ccmEnd)) {
            $ccmCountdown = 'En cours';
            $ccmCdClass   = 'bg-success';
        } else {
            $ccmDays = (int) $ccmNowFr->copy()->startOfDay()->diffInDays($ccmStart->copy()->startOfDay());
            if ($ccmDays >= 1) {
                $ccmCountdown = 'J-'.$ccmDays;
            } else {
                $ccmMin = max(1, (int) $ccmNowFr->diffInMinutes($ccmStart));
                $ccmCountdown = $ccmMin >= 60 ? 'Dans '.intdiv($ccmMin, 60).' h' : 'Dans '.$ccmMin.' min';
            }
            $ccmCdClass = 'bg-warning text-dark';
        }
    @endphp

    <style>
        .ccm-next-card { border-radius: 8px; overflow: hidden; }
        .ccm-next-top { position: relative; }
        .ccm-next-top img { display:block; width:100%; height:auto; }
        .ccm-next-top .ccm-next-fallback { height:120px; display:flex; align-items:center; justify-content:center; }
        .ccm-next-top .ccm-next-fallback i { font-size: 48px; color:#fff; opacity:.9; }
        .ccm-next-cd { position:absolute; top:10px; right:10px; font-size:14px !important; font-weight:700; padding:6px 12px; border-radius:20px; box-shadow:0 2px 8px rgba(0,0,0,.3); }
        .ccm-next-label { position:absolute; top:10px; left:10px; font-size:12px !important; font-weight:600; padding:5px 10px; border-radius:20px; background:rgba(0,0,0,.55); color:#fff !important; }
        .ccm-next-date { display:inline-flex; flex-direction:column; align-items:center; background: var(--bs-tertiary-bg); border-radius: 12px; padding: 6px 14px; line-height:1.3; }
        .ccm-next-date .ccm-z { font-weight:600; font-size:14px !important; }
        .ccm-next-date .ccm-fr { font-size:12px !important; opacity:.75; }
        .ccm-next-leg { display:flex; align-items:center; justify-content:center; gap:10px; background: var(--bs-tertiary-bg); border-radius: 8px; padding: 8px 10px; margin-bottom: 6px; }
        .ccm-next-apt { flex:1; min-width:0; line-height:1.25; }
        .ccm-next-apt .ccm-icao { display:block; font-family:monospace; font-weight:700; font-size:16px !important; }
        .ccm-next-apt .ccm-apt-name { display:block; font-size:11px !important; opacity:.75; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
        .ccm-next-arrow { font-size:20px !important; font-weight:700; color: var(--bs-primary) !important; flex:0 0 auto; }
        .ccm-net-row { display:inline-flex; align-items:center; gap:8px; background: var(--bs-tertiary-bg); border-radius: 20px; padding: 4px 12px; font-size:13px !important; font-weight:600; }
        .ccm-net-row img { height:22px; width:auto; }
    </style>

    <div class="card border mb-0 mt-3 ccm-next-card" style="border-top:3px solid {{ $ccmColor }};">
        <div class="ccm-next-top">
            @if($ccmNext->banner_path)
                <img src="{{ public_asset('/SPTheme/images/event/'.$ccmNext->banner_path) }}?v={{ optional($ccmNext->updated_at)->timestamp }}" alt="{{ $ccmNext->title }}">
            @else
                <div class="ccm-next-fallback" style="background: linear-gradient(135deg, {{ $ccmColor }}, #1b2a4a);">
                    <i class="ph-fill {{ $ccmNext->isFlight() ? 'ph-airplane-in-flight' : 'ph-confetti' }}"></i>
                </div>
            @endif
            <span class="ccm-next-label"><i class="ph-fill ph-calendar-star align-middle me-1"></i>Prochain évènement</span>
            <span class="badge ccm-next-cd {{ $ccmCdClass }}">{{ $ccmCountdown }}</span>
        </div>

        <div class="card-body text-center">
            <h5 class="mb-2">{{ $ccmNext->title }}</h5>
            <span class="badge mb-3 align-self-center" style="background: {{ $ccmColor }}; color:#fff;">{{ $ccmCatName }}</span>

            @if(count($ccmNext->networks()))
              <div class="mb-3">
                <span class="ccm-net-row">
                  Réseau :
                  @foreach($ccmNext->networks() as $net)
                    @if(isset($ccmNetLogos[$net]))<img src="{{ $ccmNetLogos[$net]['src'] }}" alt="{{ $ccmNetLogos[$net]['name'] }}" title="{{ $ccmNetLogos[$net]['name'] }}">@endif
                  @endforeach
                </span>
              </div>
            @endif

            <div class="mb-3">
                <div class="ccm-next-date">
                    <span class="ccm-z"><i class="ph-fill ph-clock align-middle"></i> {{ $ccmNext->date->format('d/m/Y') }} &middot; {{ $ccmStart->copy()->utc()->format('H:i') }}Z &ndash; {{ $ccmEnd->copy()->utc()->format('H:i') }}Z</span>
                    <span class="ccm-fr">({{ $ccmStart->format('H:i') }} &ndash; {{ $ccmEnd->format('H:i') }} heure française)</span>
                </div>
            </div>

            @if($ccmNext->isFlight() && $ccmNext->legs->count())
                <div class="mb-3">
                    @foreach($ccmNext->legs as $leg)
                        <div class="ccm-next-leg">
                            <div class="ccm-next-apt">
                                <span class="ccm-icao">{{ $leg->departure_icao }}</span>
                                <span class="ccm-apt-name" title="{{ $ccmAirports[$leg->departure_icao] ?? '' }}">{{ $ccmAirports[$leg->departure_icao] ?? '' }}</span>
                            </div>
                            <span class="ccm-next-arrow">&rarr;</span>
                            <div class="ccm-next-apt">
                                <span class="ccm-icao">{{ $leg->arrival_icao }}</span>
                                <span class="ccm-apt-name" title="{{ $ccmAirports[$leg->arrival_icao] ?? '' }}">{{ $ccmAirports[$leg->arrival_icao] ?? '' }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            <p class="mb-3"><i class="ph-fill ph-users align-middle"></i> <b>{{ $ccmNext->going_count }}</b> inscrit{{ $ccmNext->going_count > 1 ? 's' : '' }}</p>

            <a href="{{ url('/evenements') }}" class="btn btn-primary w-100">
                <i class="ph-fill ph-arrow-right align-middle me-1"></i>Voir les évènements
            </a>
        </div>
    </div>
@endif