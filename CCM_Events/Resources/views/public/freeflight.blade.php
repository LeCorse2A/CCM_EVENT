@extends('app')
@section('title', 'Planifier mon vol - ' . $event->title)
@section('content')
<div class="row">
   <div class="col-12 mb-3">
      @if($event->banner_path)
      <img src="{{ public_asset('/SPTheme/images/event/'.$event->banner_path) }}" class="img-fluid card-img-top rounded mb-3" style="max-height:320px; width:100%; height:auto; object-fit:contain;" alt="{{ $event->title }}">
      @else
      <img src="{{ public_asset('/SPTheme/images/banner/evenements.jpg') }}" class="img-fluid card-img-top rounded mb-3" width="1920" height="200" alt="@lang('sptheme.banner')">
      @endif
   </div>
</div>

<div class="row">
   <div class="col-12 mb-3">
      <div class="card border mb-0">
         <div class="card-body">
            <h4 class="mt-0 mb-0 header-title border-bottom">
               <span class="badge badge-primary me-3">Leg {{ $leg->leg_number }}</span>
               <span class="fi fi-{{ strtolower(optional($depAirport)->country) }} shadow-img me-1"></span>
               {{ $leg->departure_icao }} @if(optional($depAirport)->name) - {{ $depAirport->name }} @endif
               <i class="ph-fill ph-arrow-fat-lines-right align-text-bottom fs-20 mx-3"></i>
               <span class="fi fi-{{ strtolower(optional($arrAirport)->country) }} shadow-img me-1"></span>
               {{ $leg->arrival_icao }} @if(optional($arrAirport)->name) - {{ $arrAirport->name }} @endif
            </h4>
            <p class="mb-0 mt-2">{{ $event->title }}</p>
            <p class="mb-0 text-muted small">
               <i class="ph-fill ph-clock align-middle me-1"></i>
               {{ $event->date->format('d/m/Y') }} &middot;
               {{ \Carbon\Carbon::parse($event->date->format('Y-m-d').' '.$event->start_time)->format('H:i') }}
               -
               {{ \Carbon\Carbon::parse($event->date->format('Y-m-d').' '.$event->end_time)->format('H:i') }}
            </p>
         </div>
      </div>
   </div>
</div>

<div class="row">
   <div class="col-xxl-8 col-xl-8 col-lg-6 col-md-12 col-sm-12 mb-3">
      <div class="row">
         <div class="col-sm-6 col-xl-4">
            <div class="card border">
               <div class="card-body widget-desk">
                  <div class="text-end">
                     <h4 class="mt-0 mb-0 fw-bold">
                        @if($aircraft)
                           {{ $aircraft->registration }}
                        @else
                           —
                        @endif
                     </h4>
                     <p class="mb-0">
                        @if($aircraft)
                           {{ $aircraft->name }}
                        @else
                           Avion introuvable
                        @endif
                     </p>
                  </div>
                  <div class="widget-icon"><i class="ph-fill ph-airplane"></i></div>
                  <div class="clearfix"></div>
               </div>
            </div>
         </div>
         <div class="col-sm-6 col-xl-4">
            <div class="card border">
               <div class="card-body widget-desk">
                  <div class="text-end">
                     <h4 class="mt-0 mb-0 fw-bold">{{ $leg->departure_icao }}</h4>
                     <p class="mb-0">@lang('common.departure')</p>
                  </div>
                  <div class="widget-icon"><i class="ph-fill ph-airplane-takeoff"></i></div>
                  <div class="clearfix"></div>
               </div>
            </div>
         </div>
         <div class="col-sm-6 col-xl-4">
            <div class="card border">
               <div class="card-body widget-desk">
                  <div class="text-end">
                     <h4 class="mt-0 mb-0 fw-bold">{{ $leg->arrival_icao }}</h4>
                     <p class="mb-0">@lang('common.arrival')</p>
                  </div>
                  <div class="widget-icon"><i class="ph-fill ph-airplane-landing"></i></div>
                  <div class="clearfix"></div>
               </div>
            </div>
         </div>
      </div>

      @if($event->legs->count() > 1)
      <div class="row">
         <div class="col-12">
            <div class="card border">
               <div class="card-body">
                  <h5 class="mt-0 header-title border-bottom"><i class="ph-fill ph-list-checks align-middle fs-18 me-1"></i>Toutes les legs de l'évènement</h5>
                  <ul class="list-group list-group-flush">
                     @foreach($event->legs as $eventLeg)
                     <li class="list-group-item d-flex justify-content-between align-items-center px-0 @if($eventLeg->id == $leg->id) fw-bold @endif">
                        <span class="font-monospace">
                           @if($eventLeg->id == $leg->id)<i class="ph-fill ph-arrow-right text-primary me-1"></i>@endif
                           Leg {{ $eventLeg->leg_number }} : {{ $eventLeg->departure_icao }} → {{ $eventLeg->arrival_icao }}
                        </span>
                        @if($eventLeg->id != $leg->id)
                        <a href="{{ url('/events') }}/{{ $event->id }}/freeflight/{{ $eventLeg->id }}" class="btn btn-sm btn-outline-secondary">Planifier celle-ci</a>
                        @endif
                     </li>
                     @endforeach
                  </ul>
               </div>
            </div>
         </div>
      </div>
      @endif

      <div class="row">
         <div class="col-12">
            <div class="card border mb-0">
               <div class="card-body">
                  <h5 class="mt-0 header-title border-bottom"><i class="ph-fill ph-clipboard-text align-middle fs-18 me-1"></i>Planifier ce vol</h5>
                  <form method="post" action="{{ route('DSpecial.freeflight_store') }}">
                     @csrf
                     <div class="form-group form-bg-grey rounded mb-3">
                        <div class="row">
                           <label class="col-5 control-label"><i class="ph-fill ph-file-code align-middle fs-20 me-1"></i>IATA Flight Type</label>
                           <div class="col-7">
                              <div class="input-group input-group-lg">
                                 <select name="ff_iatatype" class="form-select select2">
                                    @foreach($flight_types as $key => $name)
                                    @if(in_array($key, ['C', 'D', 'E', 'H', 'K', 'L', 'N', 'O', 'P', 'T', 'Z']))
                                    <option value="{{ $key }}" @if($key === 'E') selected @endif>{{ $name }}</option>
                                    @endif
                                    @endforeach
                                 </select>
                              </div>
                           </div>
                        </div>
                     </div>
                     <input type="hidden" name="ff_airlineid" value="{{ $user->airline_id }}">
                     <input type="hidden" name="ff_number" value="">
                     <input type="hidden" name="ff_callsign" value="{{ $fflight->callsign ?? $user->callsign }}">
                     <input type="hidden" name="ff_orig" value="{{ $leg->departure_icao }}">
                     <input type="hidden" name="ff_dest" value="{{ $leg->arrival_icao }}">
                     <input type="hidden" name="ff_aircraft" value="{{ $reservation->aircraft_id }}">
                     <input type="hidden" name="ff_id" value="{{ $fflight->id }}">
                     <input type="hidden" name="user_id" value="{{ $user->id }}">
                     <input type="hidden" name="ff_owner" value="@if(Theme::getSetting('roster_ident')) {{ $user->ident.' - ' }} @endif {{ $user->name_private }}">
                     <button class="btn btn-primary btn-lg w-100" type="submit">
                        <i class="ph-fill ph-compass-rose align-middle fs-20 me-1"></i>Créer un plan de vol SimBrief
                     </button>
                  </form>
                  <a href="{{ url('/events') }}" class="btn btn-outline-secondary w-100 mt-2">
                     <i class="ph-fill ph-arrow-left align-middle me-1"></i>Retour aux évènements
                  </a>
               </div>
            </div>
         </div>
      </div>

      @if($map_features)
      <div class="row">
         <div class="col-12">
            <div class="card border">
               <div class="card-body">
                  <h5 class="mt-0 header-title border-bottom"><i class="ph-fill ph-map-trifold align-middle fs-18 me-1"></i>@lang('common.map')</h5>
                  <div id="map" style="height: 400px"></div>
               </div>
            </div>
         </div>
      </div>
      @endif
   </div>

   <div class="col-xxl-4 col-xl-4 col-lg-6 col-md-12 col-sm-12 mb-3">
      <div class="card border mb-0">
         <div class="card-body">
            <div class="tab-default">
               <div role="tabpanel">
                  <ul class="nav nav-tabs nav-justified mb-3" role="tablist">
                     <li class="nav-item" role="presentation">
                        <a class="nav-link active" data-bs-toggle="tab" href="#dpt-tab-evt" role="tab" aria-selected="true">
                           <i class="ph-fill ph-airplane-takeoff align-middle fs-18 me-1"></i>@lang('dashboard.weatherat', ['ICAO' => $leg->departure_icao])
                        </a>
                     </li>
                     <li class="nav-item" role="presentation">
                        <a class="nav-link" data-bs-toggle="tab" href="#arr-tab-evt" role="tab" aria-selected="false" tabindex="-1">
                           <i class="ph-fill ph-airplane-landing align-middle fs-18 me-1"></i>@lang('dashboard.weatherat', ['ICAO' => $leg->arrival_icao])
                        </a>
                     </li>
                  </ul>
                  <div class="tab-content text-muted">
                     <div class="tab-pane active show" id="dpt-tab-evt" role="tabpanel">
                        <div class="p-4">
                           <h5 class="m-0"><a href="https://metar-taf.com/{{ $leg->departure_icao }}" id="metartaf-evtdpt" style="pointer-events: none">METAR {{ $leg->departure_icao }}</a></h5>
                           <script async defer crossorigin="anonymous" src="https://metar-taf.com/embed-js/{{ $leg->departure_icao }}?layout=landscape&target=evtdpt"></script>
                        </div>
                        {{ Widget::Weather(['icao' => $leg->departure_icao]) }}
                     </div>
                     <div class="tab-pane" id="arr-tab-evt" role="tabpanel">
                        <div class="p-4">
                           <h5 class="m-0"><a href="https://metar-taf.com/{{ $leg->arrival_icao }}" id="metartaf-evtarr" style="pointer-events: none">METAR {{ $leg->arrival_icao }}</a></h5>
                           <script async defer crossorigin="anonymous" src="https://metar-taf.com/embed-js/{{ $leg->arrival_icao }}?layout=landscape&target=evtarr"></script>
                        </div>
                        {{ Widget::Weather(['icao' => $leg->arrival_icao]) }}
                     </div>
                  </div>
               </div>
            </div>
         </div>
      </div>
   </div>
</div>
@endsection
@section('scripts')
@parent
<script>
   $(".select2").select2({
      width: '100%'
   });
</script>
@if($map_features)
<script type="text/javascript">
   phpvms.map.render_route_map({
      route_points: {!! json_encode($map_features['route_points']) !!},
      planned_route_line: {!! json_encode($map_features['planned_route_line']) !!},
      circle_color: '#067ec1',
      flightplan_route_color: '#067ec1',
      leafletOptions: {
         scrollWheelZoom: true,
         providers: {
            'Esri.WorldImagery': {},
         }
      }
   });
</script>
@endif
@endsection