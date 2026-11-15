@extends('admin.app')
@section('title', 'Évènements')

@section('content')
<div class="card border-blue-bottom" style="margin-left:5px; margin-right:5px;">
  <div class="content">

<link href="https://cdn.jsdelivr.net/npm/quill@1.3.6/dist/quill.snow.css" rel="stylesheet">
<style>
#ccm-events-wrap { padding: 20px; }
.ccm-tabs { display: flex; gap: 4px; border-bottom: 2px solid #E5E7EB; margin-bottom: 20px; }
.ccm-tab { padding: 10px 20px; cursor: pointer; font-weight: 600; font-size: 13px; color: #6B7280; border-bottom: 2px solid transparent; margin-bottom: -2px; }
.ccm-tab.active { color: #1354A5; border-bottom-color: #1354A5; }
.ccm-tabpanel { display: none; }
.ccm-tabpanel.active { display: block; }

.ccm-cal-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; }
.ccm-cal-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 6px; }
.ccm-cal-day { min-height: 90px; border: 1px solid #E5E7EB; border-radius: 6px; padding: 6px; background: #fff; cursor: pointer; }
.ccm-cal-day.empty { background: #F9FAFB; border: none; cursor: default; }
.ccm-cal-daynum { font-size: 11px; color: #6B7280; margin-bottom: 4px; }
.ccm-event-chip { background: #DBEAFE; color: #1354A5; border-radius: 4px; padding: 4px 6px; font-size: 11px; margin-bottom: 3px; cursor: pointer; white-space: normal; user-select: none; -webkit-user-drag: element; }
.ccm-event-chip:hover { background: #93C5FD; }
.ccm-resa-table { width: 100%; border-collapse: collapse; font-size: 12px; margin-top: 8px; }
.ccm-resa-table th { text-align: left; padding: 6px 8px; background: #F8FAFC; font-size: 10px; text-transform: uppercase; color: #6B7280; }
.ccm-resa-table td { padding: 6px 8px; border-bottom: 1px solid #F3F4F6; vertical-align: middle; }
.ccm-resa-empty { font-size: 12px; color: #6B7280; font-style: italic; padding: 8px 0; }
#ccm-description-editor { resize: vertical; min-height: 90px; max-height: 400px; overflow-y: auto; }
.ccm-cfg-row { display:flex; gap:8px; margin-bottom:6px; align-items:center; }
.ccm-cfg-row-remove { color:#D42B2B; white-space:nowrap; font-size:12px; }
.ccm-stats-table { width:100%; border-collapse:collapse; font-size:12px; margin-bottom:24px; }
.ccm-stats-table th { text-align:left; padding:8px; background:#F8FAFC; font-size:10px; text-transform:uppercase; color:#6B7280; cursor:pointer; user-select:none; white-space:nowrap; }
.ccm-stats-table td { padding:8px; border-bottom:1px solid #F3F4F6; vertical-align:top; }
.ccm-stats-table tr.ccm-clickable:hover td { background:#F0F7FF; cursor:pointer; }
.ccm-stats-badge { display:inline-block; font-size:10px; padding:2px 6px; border-radius:4px; color:#fff; margin-left:4px; }
.ccm-stats-detail { background:#F9FAFB; font-size:12px; }
.ccm-stats-detail ul { margin:4px 0 0; padding-left:18px; }
.ccm-stats-summary { display:flex; gap:12px; margin-bottom:16px; flex-wrap:wrap; }
.ccm-stats-summary div { background:#F8FAFC; border:1px solid #E5E7EB; border-radius:6px; padding:8px 14px; font-size:12px; }
.ccm-stats-summary b { font-size:16px; color:#1354A5; }
.ccm-excl-list { border:1px solid #E5E7EB; border-radius:6px; padding:8px 12px; overflow-x:auto;
  display:grid; grid-auto-flow:column; grid-template-rows:repeat(8, auto); grid-auto-columns:max-content; gap:2px 28px; justify-content:start; }
.ccm-excl-list label { display:flex; align-items:center; gap:6px; font-weight:400; font-size:12px; margin:0; padding:3px 0; white-space:nowrap; }
.ccm-excl-list label input { margin:0; }
.ccm-alert-box { display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap; background:#FFFBEB; border:1px solid #FDE68A; border-radius:6px; padding:12px 16px; margin-bottom:16px; font-size:12px; }
.ccm-stats-subtabs { display:inline-flex; border:1px solid #D1D5DB; border-radius:6px; overflow:hidden; }
.ccm-stats-subtabs button { border:none; background:#fff; padding:6px 16px; font-size:12px; font-weight:600; color:#6B7280; }
.ccm-stats-subtabs button.active { background:#1354A5; color:#fff; }
.ccm-kpis { display:grid; grid-template-columns:repeat(4, 1fr); gap:12px; margin-bottom:24px; }
.ccm-kpis div { background:#F8FAFC; border:1px solid #E5E7EB; border-left:4px solid #1354A5; border-radius:6px; padding:12px 16px; }
.ccm-kpis span { display:block; font-size:11px; color:#6B7280; text-transform:uppercase; }
.ccm-kpis b { font-size:24px; color:#1354A5; }
.ccm-kpis .ccm-kpi-alert { border-left-color:#D42B2B; }
.ccm-kpis .ccm-kpi-alert b { color:#D42B2B; }
.ccm-rate-track { width:70px; height:8px; background:#E5E7EB; border-radius:4px; overflow:hidden; }
.ccm-rate-track div { height:100%; }
.ccm-legend { font-size:11px; color:#6B7280; margin-bottom:8px; }
.ccm-legend span { display:inline-block; width:10px; height:10px; border-radius:2px; margin:0 4px 0 10px; vertical-align:middle; }
.ccm-chart-row { display:flex; align-items:center; gap:10px; margin-bottom:6px; cursor:pointer; }
.ccm-chart-row:hover .ccm-chart-label { color:#1354A5; }
.ccm-chart-label { width:320px; font-size:11px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; color:#374151; }
.ccm-chart-bar { flex:1; display:flex; height:20px; border-radius:4px; overflow:hidden; background:#F3F4F6; }
.ccm-chart-bar div { display:flex; align-items:center; justify-content:center; font-size:10px; font-weight:700; color:#fff; overflow:hidden; }
@media (max-width: 900px) { .ccm-kpis { grid-template-columns:repeat(2, 1fr); } .ccm-chart-label { width:160px; } }
#ccm-banner-preview img { max-width: 100%; max-height: 160px; border-radius: 6px; border: 1px solid #E5E7EB; }
.ccm-banner-remove-label { font-size: 12px; color: #D42B2B; display: flex; align-items: center; gap: 6px; margin-top: 6px; }

/* Libellés du menu déroulant "taille" de l'éditeur (sinon Quill affiche "Normal" pour tout) */
.ql-snow .ql-picker.ql-size .ql-picker-label[data-value="12px"]::before,
.ql-snow .ql-picker.ql-size .ql-picker-item[data-value="12px"]::before { content: 'Petit'; }
.ql-snow .ql-picker.ql-size .ql-picker-label[data-value="18px"]::before,
.ql-snow .ql-picker.ql-size .ql-picker-item[data-value="18px"]::before { content: 'Grand'; }
.ql-snow .ql-picker.ql-size .ql-picker-label[data-value="24px"]::before,
.ql-snow .ql-picker.ql-size .ql-picker-item[data-value="24px"]::before { content: 'Très grand'; }
</style>

<div id="ccm-events-wrap">

  <div class="ccm-tabs">
    <div class="ccm-tab active" data-tab="calendar" onclick="ccmSwitchTab('calendar')">Calendrier</div>
    <div class="ccm-tab" data-tab="stats" onclick="ccmSwitchTab('stats')">Statistiques</div>
    <div class="ccm-tab" data-tab="config" onclick="ccmSwitchTab('config')">Configuration</div>
  </div>

  {{-- ── Onglet Calendrier ── --}}
  <div class="ccm-tabpanel active" id="tab-calendar">
    <div class="ccm-cal-header">
      <button class="btn btn-sm btn-default" onclick="ccmChangeMonth(-1)">&laquo; Précédent</button>
      <h4 id="ccm-cal-title">{{ ucfirst(\Carbon\Carbon::create($currentYear, $currentMonth, 1)->locale('fr')->monthName) }} {{ $currentYear }}</h4>
      <div>
        <button class="btn btn-sm btn-primary" onclick="ccmOpenEventModal()">+ Nouvel évènement</button>
        <button class="btn btn-sm btn-default" onclick="ccmChangeMonth(1)">Suivant &raquo;</button>
      </div>
    </div>
    <p class="text-muted" style="font-size:11px;">Double-cliquez sur une case pour créer un évènement à cette date. &bull; <span style="display:inline-block;width:7px;height:7px;border-radius:50%;background:#10B981;"></span> = vols activés</p>

    <div class="ccm-cal-grid" id="ccm-cal-grid">
      {{-- rempli en JS depuis EVENTS_DATA --}}
    </div>
  </div>

  {{-- ── Onglet Statistiques ── --}}
  <div class="ccm-tabpanel" id="tab-stats">
    <div id="ccm-stats-content">Chargement...</div>
  </div>

  {{-- ── Onglet Config ── --}}
  <div class="ccm-tabpanel" id="tab-config">
    <div id="ccm-config-content">Chargement...</div>
  </div>

  {{-- ── Modale Envoi de mail (statistiques) ── --}}
  <div id="ccm-mail-modal" style="display:none; position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,.5); z-index:9999; align-items:center; justify-content:center;">
    <div style="background:#fff; border-radius:8px; padding:24px; width:640px; max-width:95%; max-height:90vh; overflow-y:auto;">
      <h4>Envoyer un mail</h4>
      <div id="ccm-mail-pilot" style="margin-bottom:12px;"></div>
      <label>Type de mail</label>
      <div class="ccm-stats-subtabs" style="margin-bottom:12px;">
        <button type="button" class="ccm-mail-type" data-level="reminder" onclick="ccmFillMail('reminder')">🟡 Rappel</button>
        <button type="button" class="ccm-mail-type" data-level="warning" onclick="ccmFillMail('warning')">🟠 Avertissement</button>
        <button type="button" class="ccm-mail-type" data-level="exclusion" onclick="ccmFillMail('exclusion')">🔴 Exclusion</button>
      </div>
      <div id="ccm-mail-excl-note" class="alert alert-warning" style="display:none; font-size:12px; padding:8px;">Ce mail prévient seulement le pilote : son compte n'est pas supprimé automatiquement, pensez à le retirer dans phpVMS.</div>
      <div class="form-group"><label>Objet</label><input type="text" id="ccm-mail-subject" class="form-control"></div>
      <div class="form-group"><label>Texte <span class="text-muted" style="font-weight:400; font-size:11px;">(modifiable avant l'envoi)</span></label><textarea id="ccm-mail-body" class="form-control" rows="12"></textarea></div>
      <div style="text-align:right;">
        <button type="button" class="btn btn-default" onclick="ccmCloseMailModal()">Annuler</button>
        <button type="button" class="btn btn-primary" id="ccm-mail-send" onclick="ccmSubmitMail()">Envoyer</button>
      </div>
    </div>
  </div>

  {{-- ── Modale Event (création/édition + legs + réservations) ── --}}
  <div id="ccm-event-modal" style="display:none; position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,.5); z-index:9999; align-items:center; justify-content:center;">
    <div style="background:#fff; border-radius:8px; padding:24px; width:680px; max-height:85vh; overflow-y:auto;">
      <h4 id="ccm-modal-title">Nouvel évènement</h4>
      <form id="ccm-event-form">
        <input type="hidden" id="ccm-event-id" value="">
        <div class="form-group">
          <label>Titre</label>
          <input type="text" class="form-control" id="ccm-title" required>
        </div>
        <div class="row">
          <div class="col-sm-4">
            <div class="form-group"><label>Date</label><input type="date" class="form-control" id="ccm-date" required></div>
          </div>
          <div class="col-sm-4">
            <div class="form-group"><label>Début (heure locale)</label><input type="time" class="form-control" id="ccm-start" required></div>
          </div>
          <div class="col-sm-4">
            <div class="form-group"><label>Fin (heure locale)</label><input type="time" class="form-control" id="ccm-end" required></div>
          </div>
        </div>
        <div class="form-group">
          <label>Type d'évènement</label>
          <select class="form-control" id="ccm-type" onchange="ccmApplyTypeUi()">
            <option value="flight">Évènement vol (aéroport, stand, avion)</option>
            <option value="non_flight">Évènement hors vol (ex. soirée cinéma)</option>
          </select>
        </div>
        <div class="form-group">
          <label>Catégorie</label>
          <select class="form-control" id="ccm-category"></select>
        </div>
        <div class="form-group">
          <label>Description</label>
          <div id="ccm-description-editor" style="background:#fff;"></div>
          <textarea id="ccm-description" style="display:none;"></textarea>
        </div>

        {{-- ── Bannière de l'évènement ── --}}
        <div class="form-group">
          <label>Bannière de l'évènement <span style="font-weight:400; font-size:11px; color:#6B7280;">(optionnel, tout format d'image, 20 Mo max — redimensionnée automatiquement si trop grande)</span></label>
          <div id="ccm-banner-preview" style="display:none; margin-bottom:8px;"></div>
          <input type="file" class="form-control" id="ccm-banner-input" accept="image/*">
          <label class="ccm-banner-remove-label" id="ccm-banner-remove-wrap" style="display:none;">
            <input type="checkbox" id="ccm-banner-remove"> Supprimer la bannière actuelle
          </label>
        </div>

        {{-- ── Legs (trajets) ── --}}
        <div class="form-group" id="ccm-legs-section">
          <label>Trajets (legs)</label>
          <div id="ccm-legs-list"></div>
          <div style="display:flex; gap:8px; margin-top:8px;">
            <input type="text" class="form-control" id="ccm-leg-dep" placeholder="ICAO départ" maxlength="4" style="text-transform:uppercase;">
            <input type="text" class="form-control" id="ccm-leg-arr" placeholder="ICAO arrivée" maxlength="4" style="text-transform:uppercase;">
            <button type="button" class="btn btn-sm btn-default" onclick="ccmAddLeg()">Ajouter</button>
          </div>
          <p class="text-muted" style="font-size:11px; margin-top:4px;">Le trajet global de l'évènement est défini par le 1er départ et la dernière arrivée des legs.</p>
        </div>

        {{-- ── Réservations / Positionnement avion (visible en édition uniquement) ── --}}
        <div class="form-group" id="ccm-resa-section" style="display:none;">
          <label>Réservations pilotes &amp; positionnement avion</label>
          <div id="ccm-resa-list"></div>
        </div>

        <div style="display:flex; justify-content:space-between; margin-top:20px;">
          <div>
            <button type="button" class="btn btn-danger" id="ccm-delete-btn" onclick="ccmDeleteEvent()" style="display:none;">Supprimer</button>
            <button type="button" class="btn btn-success" id="ccm-toggle-flight-btn" onclick="ccmToggleFlight()" style="display:none;">Activer/Désactiver les vols</button>
          </div>
          <div>
            <button type="button" class="btn btn-default" onclick="ccmCloseModal()">Annuler</button>
            <button type="submit" class="btn btn-primary">Enregistrer</button>
          </div>
        </div>
      </form>
    </div>
  </div>

</div>

  </div>
</div>
@endsection

@section('scripts')
@parent
<script src="https://cdn.jsdelivr.net/npm/quill@1.3.6/dist/quill.min.js"></script>
<script>
const EVENTS_URL = "{{ route('ccm_events.show', ['id' => 0]) }}".replace('/0', '');
const EVENT_BANNER_BASE = "{{ public_asset('/SPTheme/images/event') }}";
const CATEGORIES = @json($categories);
let currentMonth = {{ $currentMonth }};
let currentYear  = {{ $currentYear }};
let EVENTS_DATA  = @json($events);

// ── Éditeur riche (Quill) pour la description ──
// Taille appliquée en style inline (font-size), pas de classe CSS,
// pour que le HTML stocké s'affiche correctement côté public sans
// dépendre de la CSS de Quill.
const CcmSizeStyle = Quill.import('attributors/style/size');
CcmSizeStyle.whitelist = ['12px', '18px', '24px'];
Quill.register(CcmSizeStyle, true);

let ccmQuill = new Quill('#ccm-description-editor', {
  theme: 'snow',
  modules: {
    toolbar: [
      ['bold', 'italic', 'underline'],
      [{ 'size': ['12px', false, '18px', '24px'] }],
      ['clean']
    ]
  }
});
ccmQuill.on('text-change', function() {
  document.getElementById('ccm-description').value = ccmQuill.root.innerHTML;
});

function csrfHeaders() {
  // Accept: application/json -> Laravel répond en JSON (et non en page HTML) aux erreurs
  return { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content };
}
function csrfHeadersJson() {
  return { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content };
}

function ccmSwitchTab(tab) {
  document.querySelectorAll('.ccm-tab').forEach(t => t.classList.toggle('active', t.dataset.tab === tab));
  document.querySelectorAll('.ccm-tabpanel').forEach(p => p.classList.toggle('active', p.id === 'tab-' + tab));
  if (tab === 'config') ccmLoadConfig();
  if (tab === 'stats') ccmLoadStats();
}

function ccmChangeMonth(delta) {
  currentMonth += delta;
  if (currentMonth < 1) { currentMonth = 12; currentYear--; }
  if (currentMonth > 12) { currentMonth = 1; currentYear++; }
  window.location.href = `${EVENTS_URL}?month=${currentMonth}&year=${currentYear}`;
}

function ccmRenderCalendar() {
  const grid = document.getElementById('ccm-cal-grid');
  grid.innerHTML = '';
  const firstDay = new Date(currentYear, currentMonth - 1, 1);
  const lastDay  = new Date(currentYear, currentMonth, 0);
  let startOffset = firstDay.getDay() === 0 ? 6 : firstDay.getDay() - 1;

  for (let i = 0; i < startOffset; i++) {
    grid.insertAdjacentHTML('beforeend', '<div class="ccm-cal-day empty"></div>');
  }

  for (let d = 1; d <= lastDay.getDate(); d++) {
    const dateStr = `${currentYear}-${String(currentMonth).padStart(2,'0')}-${String(d).padStart(2,'0')}`;
    const dayEvents = EVENTS_DATA.filter(e => e.date.startsWith(dateStr));

    let chips = dayEvents.map(e => {
      const legsText = (e.legs && e.legs.length)
        ? `<div style="font-size:10px; opacity:.8;">${e.legs.map(l => l.departure_icao + '→' + l.arrival_icao).join(', ')}</div>`
        : '';
      const isFlight = (e.type || 'flight') === 'flight';
      const flightOn = isFlight && !!e.flights_enabled;
      const typeIcon = isFlight ? '' : '<span title="Évènement hors vol">🎟️</span> ';
      const badge = flightOn ? '<span style="display:inline-block;width:7px;height:7px;border-radius:50%;background:#10B981;margin-right:4px;"></span>' : '';
      return `<div class="ccm-event-chip" draggable="true" data-id="${e.id}" ondragstart="ccmDragStart(event,${e.id})" onclick="event.stopPropagation(); ccmOpenEventModal(${e.id})">
                <div>${badge}${typeIcon}${e.start_time.substring(0,5)} ${e.title}</div>
                ${legsText}
              </div>`;
    }).join('');

    grid.insertAdjacentHTML('beforeend',
      `<div class="ccm-cal-day" ondragover="event.preventDefault()" ondrop="ccmDrop(event,'${dateStr}')" ondblclick="ccmOpenEventModal(null, '${dateStr}')">
         <div class="ccm-cal-daynum">${d}</div>${chips}
       </div>`);
  }
}

let draggedEventId = null;
function ccmDragStart(e, id) {
  draggedEventId = id;
  e.dataTransfer.setData('text/plain', id);
}
function ccmDrop(e, newDate) {
  e.preventDefault();
  if (!draggedEventId) return;
  const ev = EVENTS_DATA.find(x => x.id === draggedEventId);
  if (!ev) return;
  const fd = new FormData();
  fd.append('_method', 'PUT');
  fd.append('title', ev.title);
  fd.append('date', newDate);
  fd.append('start_time', ev.start_time.substring(0,5));
  fd.append('end_time', ev.end_time.substring(0,5));
  fd.append('category', ev.category || '');
  fd.append('type', ev.type || 'flight');
  fd.append('description', ev.description || '');
  fetch(`${EVENTS_URL}/${draggedEventId}`, { method: 'POST', headers: csrfHeaders(), body: fd })
    .then(r => r.json()).then(data => {
      if (data.success) { ev.date = newDate; ccmRenderCalendar(); }
      else alert('Erreur lors du déplacement');
    });
}

function ccmResetBannerFields() {
  document.getElementById('ccm-banner-input').value = '';
  document.getElementById('ccm-banner-remove').checked = false;
  document.getElementById('ccm-banner-remove-wrap').style.display = 'none';
  document.getElementById('ccm-banner-preview').style.display = 'none';
  document.getElementById('ccm-banner-preview').innerHTML = '';
}

document.getElementById('ccm-banner-input').addEventListener('change', function() {
  const preview = document.getElementById('ccm-banner-preview');
  if (!this.files.length) { preview.style.display = 'none'; preview.innerHTML = ''; return; }
  const url = URL.createObjectURL(this.files[0]);
  preview.style.display = 'block';
  preview.innerHTML = `<img src="${url}" alt="Aperçu bannière">`;
});

function ccmOpenEventModal(id = null, prefillDate = null) {
  document.getElementById('ccm-event-form').reset();
  document.getElementById('ccm-legs-list').innerHTML = '';
  document.getElementById('ccm-event-id').value = '';
  document.getElementById('ccm-delete-btn').style.display = 'none';
  document.getElementById('ccm-toggle-flight-btn').style.display = 'none';
  document.getElementById('ccm-resa-section').style.display = 'none';
  document.getElementById('ccm-resa-list').innerHTML = '';
  ccmResetBannerFields();
  ccmQuill.setContents([]);
  document.getElementById('ccm-description').value = '';

  const catSel = document.getElementById('ccm-category');
  catSel.innerHTML = Object.entries(CATEGORIES).map(([k,v]) => `<option value="${k}">${v}</option>`).join('');

  if (id) {
    document.getElementById('ccm-modal-title').textContent = "Modifier l'évènement";
    fetch(`${EVENTS_URL}/${id}`).then(r => r.json()).then(ev => {
      document.getElementById('ccm-event-id').value = ev.id;
      document.getElementById('ccm-title').value = ev.title;
      document.getElementById('ccm-date').value = ev.date.substring(0,10);
      document.getElementById('ccm-start').value = ev.start_time.substring(0,5);
      document.getElementById('ccm-end').value = ev.end_time.substring(0,5);
      document.getElementById('ccm-category').value = ev.category;
      document.getElementById('ccm-type').value = ev.type || 'flight';
      ccmQuill.root.innerHTML = ev.description || '';
      document.getElementById('ccm-description').value = ev.description || '';
      document.getElementById('ccm-delete-btn').style.display = 'inline-block';

      if (ev.banner_path) {
        const preview = document.getElementById('ccm-banner-preview');
        preview.style.display = 'block';
        preview.innerHTML = `<img src="${EVENT_BANNER_BASE}/${ev.banner_path}?v=${Date.now()}" alt="Bannière actuelle">`;
        document.getElementById('ccm-banner-remove-wrap').style.display = 'flex';
      }

      const flightBtn = document.getElementById('ccm-toggle-flight-btn');
      flightBtn.style.display = 'inline-block';
      flightBtn.dataset.eventId = ev.id;
      flightBtn.textContent = ev.flights_enabled ? 'Désactiver les vols' : 'Activer les vols';
      flightBtn.classList.toggle('btn-success', !ev.flights_enabled);
      flightBtn.classList.toggle('btn-warning', !!ev.flights_enabled);

      (ev.legs || []).forEach(leg => ccmRenderLeg(leg));

      document.getElementById('ccm-resa-section').style.display = 'block';
      ccmRenderReservations(ev.reservations || []);
      ccmApplyTypeUi();
    });
  } else {
    document.getElementById('ccm-modal-title').textContent = 'Nouvel évènement';
    if (prefillDate) document.getElementById('ccm-date').value = prefillDate;
  }
  ccmApplyTypeUi();
  document.getElementById('ccm-event-modal').style.display = 'flex';
}

// Évènement hors vol : on masque trajets, réservations et bouton « Activer les vols »
function ccmApplyTypeUi() {
  const isFlight = document.getElementById('ccm-type').value === 'flight';
  const isEdit   = !!document.getElementById('ccm-event-id').value;
  document.getElementById('ccm-legs-section').style.display = isFlight ? '' : 'none';
  document.getElementById('ccm-toggle-flight-btn').style.display = (isFlight && isEdit) ? 'inline-block' : 'none';
  document.getElementById('ccm-resa-section').style.display = (isFlight && isEdit) ? 'block' : 'none';
}
function ccmCloseModal() { document.getElementById('ccm-event-modal').style.display = 'none'; }

function ccmRenderLeg(leg) {
  document.getElementById('ccm-legs-list').insertAdjacentHTML('beforeend',
    `<div data-leg-id="${leg.id || ''}" style="display:flex; justify-content:space-between; padding:4px 0;">
       <span>${leg.departure_icao} → ${leg.arrival_icao}</span>
       <a href="#" onclick="ccmRemoveLeg(event, ${leg.id})" style="color:#D42B2B;">Retirer</a>
     </div>`);
}

function ccmAddLeg() {
  const dep = document.getElementById('ccm-leg-dep').value.toUpperCase().trim();
  const arr = document.getElementById('ccm-leg-arr').value.toUpperCase().trim();
  const eventId = document.getElementById('ccm-event-id').value;
  if (dep.length !== 4 || arr.length !== 4) { alert('Codes ICAO invalides (4 caractères)'); return; }
  if (!eventId) { alert("Enregistrez d'abord l'évènement avant d'ajouter un trajet."); return; }

  fetch(`${EVENTS_URL}/${eventId}/legs`, {
    method: 'POST', headers: csrfHeadersJson(),
    body: JSON.stringify({ departure_icao: dep, arrival_icao: arr })
  }).then(r => r.json()).then(data => {
    if (data.success) {
      ccmRenderLeg(data.leg);
      document.getElementById('ccm-leg-dep').value = '';
      document.getElementById('ccm-leg-arr').value = '';
    }
  });
}

function ccmRemoveLeg(e, legId) {
  e.preventDefault();
  const eventId = document.getElementById('ccm-event-id').value;
  fetch(`${EVENTS_URL}/${eventId}/legs/${legId}`, { method: 'DELETE', headers: csrfHeadersJson() })
    .then(r => r.json()).then(data => { if (data.success) e.target.closest('div[data-leg-id]').remove(); });
}

function ccmDeleteEvent() {
  const id = document.getElementById('ccm-event-id').value;
  if (!id || !confirm('Supprimer cet évènement ?')) return;
  fetch(`${EVENTS_URL}/${id}`, { method: 'DELETE', headers: csrfHeadersJson() })
    .then(r => r.json()).then(data => { if (data.success) window.location.reload(); });
}

function ccmToggleFlight() {
  const btn = document.getElementById('ccm-toggle-flight-btn');
  const id = btn.dataset.eventId;
  fetch(`${EVENTS_URL}/${id}/flight/toggle`, { method: 'POST', headers: csrfHeadersJson() })
    .then(r => r.json()).then(data => {
      if (!data.success) { alert('Erreur'); return; }

      btn.textContent = data.active ? 'Désactiver les vols' : 'Activer les vols';
      btn.classList.toggle('btn-success', !data.active);
      btn.classList.toggle('btn-warning', !!data.active);

      const ev = EVENTS_DATA.find(x => x.id == id);
      if (ev) { ev.flights_enabled = data.active; ccmRenderCalendar(); }
    });
}

// ── Réservations / Positionnement avion ──
function ccmRenderReservations(reservations) {
  const box = document.getElementById('ccm-resa-list');
  if (!reservations.length) {
    box.innerHTML = '<div class="ccm-resa-empty">Aucune réservation pour cet évènement.</div>';
    return;
  }

  let rows = reservations.map(r => {
    const pilot = r.user_name || ('Pilote #' + r.user_id);
    const aircraft = r.aircraft ? (r.aircraft.icao + ' | ' + r.aircraft.registration) : '—';
    const positioned = !!r.original_airport_id;
    let action;
    if (!r.aircraft) {
      action = '<span class="text-muted">—</span>';
    } else if (!positioned) {
      action = `<button type="button" class="btn btn-xs btn-primary" onclick="ccmPositionAircraft(${r.id}, '${r.airport_icao}')">Positionner</button>`;
    } else {
      action = `<span class="text-success" style="font-size:11px;">✓ Positionné</span> <button type="button" class="btn btn-xs btn-default" onclick="ccmReturnAircraft(${r.id}, '${r.original_airport_id}')">Retour origine</button>`;
    }
    return `<tr>
      <td>${pilot}</td>
      <td>${r.airport_icao}${r.stand ? ' · ' + r.stand : ''}</td>
      <td>${aircraft}</td>
      <td>${action}</td>
    </tr>`;
  }).join('');

  box.innerHTML = `<table class="ccm-resa-table">
    <thead><tr><th>Pilote</th><th>Aéroport</th><th>Avion</th><th>Action</th></tr></thead>
    <tbody>${rows}</tbody>
  </table>`;
}

function ccmPositionAircraft(reservationId, targetAirport) {
  if (!confirm(`Positionner cet avion à ${targetAirport} ?`)) return;
  fetch(`${EVENTS_URL}/reservations/${reservationId}/position`, { method: 'POST', headers: csrfHeadersJson() })
    .then(r => r.json()).then(data => {
      if (data.success) {
        const id = document.getElementById('ccm-event-id').value;
        fetch(`${EVENTS_URL}/${id}`).then(r => r.json()).then(ev => ccmRenderReservations(ev.reservations || []));
      } else { alert('Erreur : ' + data.message); }
    });
}

function ccmReturnAircraft(reservationId, originalAirport) {
  if (!confirm(`Retourner cet avion à ${originalAirport} ?`)) return;
  fetch(`${EVENTS_URL}/reservations/${reservationId}/return`, { method: 'POST', headers: csrfHeadersJson() })
    .then(r => r.json()).then(data => {
      if (data.success) {
        const id = document.getElementById('ccm-event-id').value;
        fetch(`${EVENTS_URL}/${id}`).then(r => r.json()).then(ev => ccmRenderReservations(ev.reservations || []));
      } else { alert('Erreur : ' + data.message); }
    });
}

// Réduit l'image dans le navigateur AVANT l'envoi (max 1600 px de large, WebP) :
// évite de dépasser la limite d'envoi du serveur avec une grosse image.
function ccmShrinkImage(file, maxW = 1600) {
  return new Promise(resolve => {
    if (!file.type.startsWith('image/') || file.type === 'image/gif') return resolve(file); // GIF : on garde l'animation
    const url = URL.createObjectURL(file);
    const img = new Image();
    img.onload = () => {
      URL.revokeObjectURL(url);
      if (img.width <= maxW && file.size <= 1500000) return resolve(file); // déjà légère
      const scale = Math.min(1, maxW / img.width);
      const c = document.createElement('canvas');
      c.width  = Math.round(img.width * scale);
      c.height = Math.round(img.height * scale);
      c.getContext('2d').drawImage(img, 0, 0, c.width, c.height);
      c.toBlob(blob => {
        if (!blob || blob.size >= file.size) return resolve(file);
        const ext = blob.type === 'image/webp' ? 'webp' : 'png';
        resolve(new File([blob], file.name.replace(/\.[^.]+$/, '') + '.' + ext, { type: blob.type }));
      }, 'image/webp', 0.85);
    };
    img.onerror = () => { URL.revokeObjectURL(url); resolve(file); };
    img.src = url;
  });
}

document.getElementById('ccm-event-form').addEventListener('submit', function(e) {
  e.preventDefault();
  const id = document.getElementById('ccm-event-id').value;

  const fd = new FormData();
  fd.append('title', document.getElementById('ccm-title').value);
  fd.append('date', document.getElementById('ccm-date').value);
  fd.append('start_time', document.getElementById('ccm-start').value);
  fd.append('end_time', document.getElementById('ccm-end').value);
  fd.append('category', document.getElementById('ccm-category').value);
  fd.append('type', document.getElementById('ccm-type').value);
  fd.append('description', document.getElementById('ccm-description').value);

  const bannerInput = document.getElementById('ccm-banner-input');
  if (id && document.getElementById('ccm-banner-remove').checked) {
    fd.append('remove_banner', '1');
  }

  const url = id ? `${EVENTS_URL}/${id}` : EVENTS_URL;
  if (id) fd.append('_method', 'PUT'); // spoofing PUT, requis pour envoyer un fichier

  (bannerInput.files.length ? ccmShrinkImage(bannerInput.files[0]) : Promise.resolve(null))
    .then(bannerFile => {
      if (bannerFile) fd.append('banner', bannerFile);
      return fetch(url, { method: 'POST', headers: csrfHeaders(), body: fd });
    })
    .then(r => r.json().catch(() => ({})).then(data => ({ ok: r.ok, status: r.status, data })))
    .then(({ ok, status, data }) => {
      if (ok && data.success) { window.location.reload(); return; }

      let msg = "Erreur lors de l'enregistrement.";
      if (status === 413) {
        msg = "Image trop lourde pour le serveur (limite d'envoi PHP). Essaie une image plus légère.";
      } else if (status === 422 && data.errors) {
        msg = Object.values(data.errors).flat().join('\n');
        if (data.errors.banner) {
          msg = "L'image n'a pas pu être envoyée : elle dépasse la limite du serveur. Essaie une image plus légère.";
        }
        // Requête reçue vide (champs obligatoires "manquants") alors qu'une image est jointe :
        // c'est le signe que le fichier dépasse post_max_size côté serveur
        if (bannerInput.files.length && data.errors.title) {
          msg += "\n\nL'image dépasse sans doute la limite d'envoi du serveur : essaie une image plus légère.";
        }
      } else if (data.message) {
        msg = data.message;
      }
      alert(msg);
    })
    .catch(() => alert('Erreur de communication avec le serveur.'));
});

function ccmLoadConfig() {
  fetch("{{ route('ccm_events.config.index') }}").then(r => r.json()).then(data => {
    let html = '<h4>Bannière du mois</h4>';
    html += '<div id="ccm-cfg-banner-preview" style="margin-bottom:10px;"></div>';
    html += '<input type="file" id="ccm-cfg-banner-input" accept="image/png,image/jpeg" class="form-control" style="max-width:400px; display:inline-block;">';
    html += ' <button type="button" class="btn btn-sm btn-primary" onclick="ccmUploadBanner()">Envoyer la bannière</button>';
    html += ' <button type="button" class="btn btn-sm btn-danger" onclick="ccmDeleteBanner()">Supprimer la bannière</button>';
    html += '<p class="text-muted" style="font-size:11px; margin-top:4px;">Format JPG ou PNG, 5 Mo max. La nouvelle image remplace automatiquement l\'ancienne.</p>';

    html += '<hr style="margin:24px 0;">';
    html += '<h4>Catégories</h4><div id="ccm-cfg-categories"></div><button class="btn btn-sm btn-default" onclick="ccmAddCategoryRow()">+ Catégorie</button>';
    html += '<h4 style="margin-top:24px;">Aéroports & stands</h4><div id="ccm-cfg-airports"></div><button class="btn btn-sm btn-default" onclick="ccmAddAirportRow()">+ Aéroport</button>';
    html += '<h4 style="margin-top:24px;">Pilotes exclus des statistiques</h4>';
    html += '<p class="text-muted" style="font-size:11px;">Cochez les comptes à ne pas compter (ex. administrateurs qui ne volent pas). Les comptes supprimés ne sont jamais comptés.</p>';
    html += '<input type="text" class="form-control" placeholder="Rechercher un pilote..." oninput="ccmFilterExcl(this.value)" style="max-width:300px; margin-bottom:6px;">';
    html += '<div class="ccm-excl-list" id="ccm-cfg-excluded"></div>';
    html += '<h4 style="margin-top:24px;">Rappels et avertissements (participation aux évènements)</h4>';
    html += '<p class="text-muted" style="font-size:11px;">Nombre d\'évènements vol manqués à la suite (sans réponse) avant chaque niveau. Variables utilisables dans les mails : {prenom}, {callsign}, {nb_absences}.</p>';
    html += '<div style="display:flex; gap:12px; flex-wrap:wrap; margin-bottom:12px;">'
      + '<label style="font-weight:400;">🟡 Rappel dès <input type="number" min="1" id="cfg-th-reminder" class="form-control" style="width:80px; display:inline-block;"></label>'
      + '<label style="font-weight:400;">🟠 Avertissement dès <input type="number" min="1" id="cfg-th-warning" class="form-control" style="width:80px; display:inline-block;"></label>'
      + '<label style="font-weight:400;">🔴 Exclusion proposée dès <input type="number" min="1" id="cfg-th-exclusion" class="form-control" style="width:80px; display:inline-block;"></label>'
      + '</div>';
    html += '<label>Mail de rappel — objet</label><input type="text" id="cfg-mail-reminder-subject" class="form-control">';
    html += '<label style="margin-top:6px;">Mail de rappel — texte</label><textarea id="cfg-mail-reminder-body" class="form-control" rows="9"></textarea>';
    html += '<label style="margin-top:12px;">Mail d\'avertissement — objet</label><input type="text" id="cfg-mail-warning-subject" class="form-control">';
    html += '<label style="margin-top:6px;">Mail d\'avertissement — texte</label><textarea id="cfg-mail-warning-body" class="form-control" rows="9"></textarea>';
    html += '<label style="margin-top:12px;">Mail d\'exclusion — objet</label><input type="text" id="cfg-mail-exclusion-subject" class="form-control">';
    html += '<label style="margin-top:6px;">Mail d\'exclusion — texte</label><textarea id="cfg-mail-exclusion-body" class="form-control" rows="9"></textarea>';
    html += '<div style="margin-top:20px;"><button class="btn btn-primary" onclick="ccmSaveConfig()">Enregistrer la configuration</button></div>';
    document.getElementById('ccm-config-content').innerHTML = html;

    ccmRefreshBannerPreview();

    Object.entries(data.categories).forEach(([key, label]) => ccmAddCategoryRow(key, label));
    Object.entries(data.airports).forEach(([icao, stands]) => ccmAddAirportRow(icao, stands.join(',')));

    const rem = data.reminders || { thresholds: {}, mails: {} };
    rem.mails.reminder = rem.mails.reminder || {}; rem.mails.warning = rem.mails.warning || {}; rem.mails.exclusion = rem.mails.exclusion || {};
    document.getElementById('cfg-th-reminder').value  = rem.thresholds.reminder ?? 3;
    document.getElementById('cfg-th-warning').value   = rem.thresholds.warning ?? 5;
    document.getElementById('cfg-th-exclusion').value = rem.thresholds.exclusion ?? 7;
    document.getElementById('cfg-mail-reminder-subject').value = rem.mails.reminder.subject || '';
    document.getElementById('cfg-mail-reminder-body').value    = rem.mails.reminder.body || '';
    document.getElementById('cfg-mail-warning-subject').value  = rem.mails.warning.subject || '';
    document.getElementById('cfg-mail-warning-body').value     = rem.mails.warning.body || '';
    document.getElementById('cfg-mail-exclusion-subject').value = rem.mails.exclusion.subject || '';
    document.getElementById('cfg-mail-exclusion-body').value    = rem.mails.exclusion.body || '';

    const excluded = (data.stats_excluded || []).map(String);
    document.getElementById('ccm-cfg-excluded').innerHTML = (data.pilots || []).map(p => `
      <label data-search="${ccmEsc(p.label).toLowerCase()}">
        <input type="checkbox" class="cfg-excl-cb" value="${p.id}" ${excluded.includes(String(p.id)) ? 'checked' : ''}> ${ccmEsc(p.label)}
      </label>`).join('') || '<span class="text-muted">Aucun pilote.</span>';
  });
}

function ccmFilterExcl(q) {
  q = q.trim().toLowerCase();
  document.querySelectorAll('#ccm-cfg-excluded label').forEach(l => {
    l.style.display = !q || l.dataset.search.includes(q) ? '' : 'none';
  });
}

function ccmRefreshBannerPreview() {
  const preview = document.getElementById('ccm-cfg-banner-preview');
  if (!preview) return;
  const url = "{{ public_asset('/SPTheme/images/event/event.jpg') }}?v=" + Date.now();
  const img = new Image();
  img.onload = () => { preview.innerHTML = `<img src="${url}" style="max-width:400px; border-radius:6px; border:1px solid #E5E7EB;">`; };
  img.onerror = () => { preview.innerHTML = '<p class="text-muted" style="font-size:12px;">Aucune bannière actuellement.</p>'; };
  img.src = url;
}

function ccmUploadBanner() {
  const input = document.getElementById('ccm-cfg-banner-input');
  if (!input.files.length) { alert('Choisissez une image.'); return; }

  const formData = new FormData();
  formData.append('banner', input.files[0]);

  fetch("{{ route('ccm_events.config.banner') }}", {
    method: 'POST',
    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
    body: formData
  }).then(r => r.json()).then(data => {
    if (data.success) {
      alert(data.message);
      input.value = '';
      ccmRefreshBannerPreview();
    } else {
      alert('Erreur lors de l\'envoi.');
    }
  });
}

function ccmDeleteBanner() {
  if (!confirm('Supprimer la bannière actuelle ?')) return;
  fetch("{{ route('ccm_events.config.banner.delete') }}", {
    method: 'DELETE',
    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
  }).then(r => r.json()).then(data => {
    if (data.success) {
      alert(data.message);
      ccmRefreshBannerPreview();
    } else {
      alert('Erreur lors de la suppression.');
    }
  });
}

function ccmRemoveConfigRow(e) {
  e.preventDefault();
  if (!confirm('Supprimer cette ligne ? Elle sera définitivement retirée une fois la configuration enregistrée.')) return;
  e.target.closest('.ccm-cfg-row').remove();
}

function ccmAddCategoryRow(key = '', label = '') {
  document.getElementById('ccm-cfg-categories').insertAdjacentHTML('beforeend',
    `<div class="ccm-cfg-row">
       <input class="form-control cfg-cat-key" placeholder="clé" value="${key}" style="width:150px;">
       <input class="form-control cfg-cat-label" placeholder="Libellé" value="${label}">
       <a href="#" class="ccm-cfg-row-remove" onclick="ccmRemoveConfigRow(event)">Retirer</a>
     </div>`);
}
function ccmAddAirportRow(icao = '', stands = '') {
  document.getElementById('ccm-cfg-airports').insertAdjacentHTML('beforeend',
    `<div class="ccm-cfg-row">
       <input class="form-control cfg-ap-icao" placeholder="ICAO" value="${icao}" style="width:100px; text-transform:uppercase;">
       <input class="form-control cfg-ap-stands" placeholder="Stands séparés par virgule" value="${stands}">
       <a href="#" class="ccm-cfg-row-remove" onclick="ccmRemoveConfigRow(event)">Retirer</a>
     </div>`);
}
function ccmSaveConfig() {
  const categories = {};
  document.querySelectorAll('#ccm-cfg-categories > div').forEach(row => {
    const key = row.querySelector('.cfg-cat-key').value.trim();
    const label = row.querySelector('.cfg-cat-label').value.trim();
    if (key) categories[key] = label;
  });
  const airports = {};
  document.querySelectorAll('#ccm-cfg-airports > div').forEach(row => {
    const icao = row.querySelector('.cfg-ap-icao').value.trim().toUpperCase();
    const stands = row.querySelector('.cfg-ap-stands').value.split(',').map(s => s.trim()).filter(Boolean);
    if (icao) airports[icao] = stands;
  });

  fetch("{{ route('ccm_events.config.update') }}", {
    method: 'POST', headers: csrfHeadersJson(),
    body: JSON.stringify({
      categories,
      airports,
      stats_excluded_users: Array.from(document.querySelectorAll('.cfg-excl-cb:checked')).map(cb => parseInt(cb.value)),
      reminders: {
        thresholds: {
          reminder:  parseInt(document.getElementById('cfg-th-reminder').value) || 3,
          warning:   parseInt(document.getElementById('cfg-th-warning').value) || 5,
          exclusion: parseInt(document.getElementById('cfg-th-exclusion').value) || 7,
        },
        mails: {
          reminder: { subject: document.getElementById('cfg-mail-reminder-subject').value, body: document.getElementById('cfg-mail-reminder-body').value },
          warning:  { subject: document.getElementById('cfg-mail-warning-subject').value,  body: document.getElementById('cfg-mail-warning-body').value },
          exclusion:{ subject: document.getElementById('cfg-mail-exclusion-subject').value, body: document.getElementById('cfg-mail-exclusion-body').value },
        },
      },
    })
  }).then(r => r.json().catch(() => ({}))).then(data => {
    if (data.success) alert('Configuration enregistrée');
    else alert(data.errors ? Object.values(data.errors).flat().join('\n') : (data.message || "Erreur lors de l'enregistrement."));
  });
}

// ── Onglet Statistiques ──
const ccmEsc = s => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
let CCM_STATS = null;
let ccmStatsView = 'events';                       // sous-onglet : 'events' ou 'pilots'
let ccmStatsMonths = 0;                            // période : 0 = tout, 3, 6
let ccmEventSort = { key: 'date_sort', dir: -1 };
let ccmPilotSort = { key: 'going', dir: 1 };
let ccmOnlyNever = false;
let ccmOnlyAlert = false;
let ccmPilotSearch = '';

function ccmLoadStats() {
  document.getElementById('ccm-stats-content').innerHTML = 'Chargement...';
  fetch("{{ route('ccm_events.stats.data') }}?months=" + ccmStatsMonths, { headers: { 'Accept': 'application/json' } })
    .then(r => r.json())
    .then(data => { CCM_STATS = data; ccmRenderStats(); })
    .catch(() => { document.getElementById('ccm-stats-content').innerHTML = '<p class="text-danger">Erreur de chargement.</p>'; });
}

const ccmNum = n => (Array.isArray(n) ? n.length : n);
const ccmCmp = (a, b) => typeof a === 'string' ? a.localeCompare(b, 'fr', { numeric: true }) : a - b;

// Barre de progression colorée pour un taux
function ccmRateBar(rate) {
  const color = rate >= 50 ? '#10B981' : (rate >= 25 ? '#F59E0B' : '#D42B2B');
  return `<div style="display:flex; align-items:center; gap:6px;">
      <div class="ccm-rate-track"><div style="width:${rate}%; background:${color};"></div></div>
      <span style="font-weight:600; color:${color};">${rate} %</span>
    </div>`;
}

function ccmDaysAgo(iso) {
  if (!iso) return '';
  const days = Math.floor((Date.now() - new Date(iso + 'T00:00:00')) / 86400000);
  if (days < 0) return 'à venir';
  if (days === 0) return "aujourd'hui";
  return `il y a ${days} j`;
}

function ccmSortArrow(sort, key) {
  return sort.key === key ? (sort.dir === 1 ? ' ▲' : ' ▼') : '';
}

function ccmRenderStats() {
  const d = CCM_STATS;
  const never = d.by_pilot.filter(p => p.going === 0).length;
  const avg = d.events.length ? (d.events.reduce((s, e) => s + e.going.length, 0) / d.events.length).toFixed(1) : 0;

  let html = `<div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:8px; margin-bottom:12px;">
      <div class="ccm-stats-subtabs">
        <button class="${ccmStatsView === 'events' ? 'active' : ''}" onclick="ccmStatsView='events'; ccmRenderStats()">Par évènement</button>
        <button class="${ccmStatsView === 'pilots' ? 'active' : ''}" onclick="ccmStatsView='pilots'; ccmRenderStats()">Par pilote</button>
      </div>
      <div style="display:flex; gap:8px; align-items:center;">
        <select class="form-control input-sm" style="width:auto;" onchange="ccmStatsMonths = parseInt(this.value); ccmLoadStats()">
          <option value="0" ${ccmStatsMonths === 0 ? 'selected' : ''}>Toute la période</option>
          <option value="6" ${ccmStatsMonths === 6 ? 'selected' : ''}>6 derniers mois</option>
          <option value="3" ${ccmStatsMonths === 3 ? 'selected' : ''}>3 derniers mois</option>
        </select>
        <button class="btn btn-sm btn-default" onclick="ccmExportCsv()">Exporter CSV</button>
      </div>
    </div>

    <div class="ccm-kpis">
      <div><span>Pilotes comptés</span><b>${d.nb_pilots}</b></div>
      <div><span>Évènements</span><b>${d.nb_events}</b></div>
      <div><span>Moyenne de participants</span><b>${avg}</b></div>
      <div class="ccm-kpi-alert"><span>Jamais participé</span><b>${never}</b></div>
    </div>`;

  html += ccmStatsView === 'events' ? ccmRenderEventsView() : ccmRenderPilotsView();
  document.getElementById('ccm-stats-content').innerHTML = html;

  // Garder le focus dans la recherche pendant la saisie
  const search = document.getElementById('ccm-pilot-search');
  if (search && ccmPilotSearch) { search.focus(); search.setSelectionRange(search.value.length, search.value.length); }
}

function ccmRenderEventsView() {
  const d = CCM_STATS;
  if (!d.events.length) return '<p class="text-muted">Aucun évènement sur cette période.</p>';

  // Graphique : barres empilées, du plus ancien au plus récent
  let html = `<h4>Participation par évènement</h4>
    <div class="ccm-legend"><span style="background:#10B981;"></span>Participent <span style="background:#D42B2B;"></span>Déclinent <span style="background:#D1D5DB;"></span>Sans réponse</div>
    <div class="ccm-chart">`;
  d.events.slice().sort((a, b) => ccmCmp(a.date_sort, b.date_sort)).forEach(e => {
    const tot = e.eligible || 1;
    const g = e.going.length * 100 / tot, dc = e.declined.length * 100 / tot, n = e.no_answer.length * 100 / tot;
    html += `<div class="ccm-chart-row" onclick="ccmToggleEventDetail(${e.id}, true)" title="${ccmEsc(e.title)}">
        <div class="ccm-chart-label">${ccmEsc(e.date)} · ${ccmEsc(e.title)}</div>
        <div class="ccm-chart-bar">
          <div style="width:${g}%; background:#10B981;">${e.going.length || ''}</div>
          <div style="width:${dc}%; background:#D42B2B;">${e.declined.length || ''}</div>
          <div style="width:${n}%; background:#D1D5DB; color:#374151;">${e.no_answer.length || ''}</div>
        </div>
      </div>`;
  });
  html += '</div>';

  // Tableau
  const s = ccmEventSort;
  const th = (key, label) => `<th onclick="ccmSortEvents('${key}')">${label}${ccmSortArrow(s, key)}</th>`;
  html += `<h4 style="margin-top:24px;">Détail</h4>
    <p class="text-muted" style="font-size:11px;">Cliquez sur un évènement pour voir les noms. « Concernés » = pilotes déjà inscrits sur le site à la date de l'évènement.</p>
    <table class="ccm-stats-table">
      <thead><tr>${th('title', 'Évènement')}${th('date_sort', 'Date')}${th('eligible', 'Concernés')}${th('going', 'Participent')}${th('declined', 'Déclinent')}${th('no_answer', 'Sans réponse')}${th('rate', 'Taux de participation')}</tr></thead>
      <tbody>`;

  d.events.slice().sort((a, b) => ccmCmp(ccmNum(a[s.key]), ccmNum(b[s.key])) * s.dir).forEach(e => {
    const badges = (!e.is_flight ? '<span class="ccm-stats-badge" style="background:#6f42c1;">Hors vol</span>' : '')
      + (e.cancelled ? '<span class="ccm-stats-badge" style="background:#F59E0B;">Annulé</span>' : '')
      + (e.deleted ? '<span class="ccm-stats-badge" style="background:#9CA3AF;">Supprimé</span>' : '');
    html += `<tr class="ccm-clickable" onclick="ccmToggleEventDetail(${e.id})">
        <td>${ccmEsc(e.title)}${badges}</td>
        <td style="white-space:nowrap;">${ccmEsc(e.date)}</td>
        <td>${e.eligible}</td>
        <td style="color:#10B981; font-weight:600;">${e.going.length}</td>
        <td style="color:#D42B2B; font-weight:600;">${e.declined.length}</td>
        <td style="color:#6B7280; font-weight:600;">${e.no_answer.length}</td>
        <td style="min-width:130px;">${ccmRateBar(e.rate)}</td>
      </tr>
      <tr id="ccm-ev-detail-${e.id}" class="ccm-stats-detail" style="display:none;"><td colspan="7"></td></tr>`;
  });
  return html + '</tbody></table>';
}

const CCM_LEVELS = {
  reminder:  { label: '🟡 Rappel',             color: '#CA8A04', mailLabel: 'Rappel',        plural: 'rappels' },
  warning:   { label: '🟠 Avertissement',      color: '#EA580C', mailLabel: 'Avertissement', plural: 'avertissements' },
  exclusion: { label: '🔴 Exclusion proposée', color: '#DC2626', mailLabel: 'Exclusion',     plural: 'exclusions' },
};
const ccmFrDate = iso => iso ? iso.split('-').reverse().join('/') : '';

// Un mail de ce niveau a-t-il déjà été envoyé depuis moins de 30 jours ?
function ccmAlreadySent(p, level) {
  if (!p.last_mail || p.last_mail.level !== level) return false;
  return (Date.now() - new Date(p.last_mail.date + 'T00:00:00')) / 86400000 < 30;
}

function ccmRenderPilotsView() {
  const d = CCM_STATS;
  const s = ccmPilotSort;
  const t = d.thresholds;
  const th = (key, label) => `<th onclick="ccmSortPilots('${key}')">${label}${ccmSortArrow(s, key)}</th>`;

  const toRemind = d.by_pilot.filter(p => p.level === 'reminder' && !ccmAlreadySent(p, 'reminder'));
  const toWarn   = d.by_pilot.filter(p => p.level === 'warning' && !ccmAlreadySent(p, 'warning'));
  const toReview = d.by_pilot.filter(p => p.level === 'exclusion');

  let html = `<div class="ccm-alert-box">
      <div><b>Absences consécutives (évènements vol)</b> — 🟡 Rappel dès ${t.reminder} · 🟠 Avertissement dès ${t.warning} · 🔴 Exclusion proposée dès ${t.exclusion}
        <div class="text-muted" style="font-size:11px;">« Je décline » et les absences déclarées ne comptent pas. Les seuils et les textes des mails se règlent dans Configuration.</div>
      </div>
      <div style="display:flex; gap:8px; flex-wrap:wrap;">
        <button class="btn btn-sm btn-warning" ${toRemind.length ? '' : 'disabled'} onclick="ccmSendBulk('reminder')">Envoyer le rappel à tous (${toRemind.length})</button>
        <button class="btn btn-sm btn-danger" ${toWarn.length ? '' : 'disabled'} onclick="ccmSendBulk('warning')">Envoyer l'avertissement à tous (${toWarn.length})</button>
        ${toReview.length ? `<span class="ccm-stats-badge" style="background:#DC2626; font-size:12px; padding:6px 10px;">${toReview.length} pilote(s) à examiner par le staff</span>` : ''}
      </div>
    </div>

    <div style="display:flex; gap:16px; align-items:center; flex-wrap:wrap; margin-bottom:8px;">
      <input type="text" id="ccm-pilot-search" class="form-control input-sm" placeholder="Rechercher un pilote..." value="${ccmEsc(ccmPilotSearch)}"
             oninput="ccmPilotSearch = this.value; ccmRenderStats()" style="max-width:260px;">
      <label style="font-weight:400; font-size:12px; margin:0;"><input type="checkbox" ${ccmOnlyNever ? 'checked' : ''} onchange="ccmOnlyNever = this.checked; ccmRenderStats()"> Jamais participé</label>
      <label style="font-weight:400; font-size:12px; margin:0;"><input type="checkbox" ${ccmOnlyAlert ? 'checked' : ''} onchange="ccmOnlyAlert = this.checked; ccmRenderStats()"> Avec une alerte (rappel, avertissement, exclusion)</label>
    </div>
    <p class="text-muted" style="font-size:11px;">Seuls les évènements après l'inscription du pilote et hors périodes d'absence sont comptés.</p>
    <table class="ccm-stats-table">
      <thead><tr>${th('label', 'Pilote')}${th('eligible', 'Concerné')}${th('going', 'Participé')}${th('declined', 'Décliné')}${th('no_answer', 'Sans réponse')}${th('absent', 'Absent')}${th('rate', 'Taux de participation')}${th('streak_sort', 'Série')}${th('last_date', 'Dernier évènement')}<th>Alerte</th></tr></thead>
      <tbody>`;

  const q = ccmPilotSearch.trim().toLowerCase();
  const list = d.by_pilot
    .filter(p => !ccmOnlyNever || p.going === 0)
    .filter(p => !ccmOnlyAlert || p.level)
    .filter(p => !q || p.label.toLowerCase().includes(q))
    .slice()
    .sort((a, b) => ccmCmp(a[s.key] ?? '', b[s.key] ?? '') * s.dir);

  list.forEach(p => {
    const neverBadge = p.going === 0
      ? (p.eligible === 0
          ? '<span class="ccm-stats-badge" style="background:#9CA3AF;">Nouveau</span>'
          : '<span class="ccm-stats-badge" style="background:#D42B2B;">Jamais participé</span>')
      : '';
    const last = p.last_date
      ? `<span title="${ccmEsc(p.last_title)}">${ccmFrDate(p.last_date)} <span class="text-muted">· ${ccmDaysAgo(p.last_date)}</span></span>`
      : '<span class="text-muted">—</span>';
    const streak = p.streak_type === 'going'
      ? `<span style="color:#10B981; font-weight:600;">✓ ${p.streak} de suite</span>`
      : (p.streak_type === 'missed' ? `<span style="color:#D42B2B; font-weight:600;">✗ absent depuis ${p.streak}</span>` : '<span class="text-muted">—</span>');

    let alert = '';
    if (p.level) {
      const L = CCM_LEVELS[p.level];
      alert = `<div style="font-weight:600; color:${L.color}; white-space:nowrap;">${L.label}</div>`;
    }
    alert += `<button class="btn btn-xs ${p.level ? 'btn-primary' : 'btn-default'}" style="margin-top:4px;" onclick="ccmOpenMailModal(${p.id})">✉ Envoyer un mail</button>`;

    // Compteur des mails déjà envoyés
    const mc = p.mail_counts || {};
    const counts = Object.keys(CCM_LEVELS).filter(k => mc[k]).map(k => `${mc[k]} ${mc[k] > 1 ? CCM_LEVELS[k].plural : CCM_LEVELS[k].mailLabel.toLowerCase()}`);
    if (counts.length) {
      alert += `<div class="text-muted" style="font-size:10px; margin-top:3px;">${counts.join(' · ')}${p.last_mail ? ' — dernier le ' + ccmFrDate(p.last_mail.date) : ''}</div>`;
    }

    html += `<tr>
        <td>${ccmEsc(p.label)}${neverBadge}${p.joined ? `<div class="text-muted" style="font-size:10px;">Inscrit le ${ccmEsc(p.joined)}</div>` : ''}</td>
        <td>${p.eligible}</td>
        <td style="color:#10B981; font-weight:600;">${p.going}</td>
        <td style="color:#D42B2B; font-weight:600;">${p.declined}</td>
        <td style="color:#6B7280; font-weight:600;">${p.no_answer}</td>
        <td style="color:#9CA3AF;">${p.absent}</td>
        <td style="min-width:130px;">${ccmRateBar(p.rate)}</td>
        <td style="white-space:nowrap;">${streak}</td>
        <td style="white-space:nowrap;">${last}</td>
        <td>${alert}</td>
      </tr>`;
  });
  if (!list.length) html += '<tr><td colspan="10" class="text-muted">Aucun pilote.</td></tr>';
  return html + '</tbody></table>';
}

// ── Envoi des mails ──
function ccmPostReminders(payload) {
  return fetch("{{ route('ccm_events.stats.reminders') }}", { method: 'POST', headers: csrfHeadersJson(), body: JSON.stringify(payload) })
    .then(r => r.json().catch(() => ({})))
    .then(data => { alert(data.message || (data.errors ? Object.values(data.errors).flat().join('\n') : "Erreur lors de l'envoi.")); ccmLoadStats(); return data; })
    .catch(() => alert('Erreur de communication avec le serveur.'));
}

// Envoi groupé avec les modèles de Configuration
function ccmSendBulk(level) {
  const pilots = CCM_STATS.by_pilot.filter(p => p.level === level && !ccmAlreadySent(p, level));
  if (!pilots.length) return;
  const what = level === 'warning' ? "l'avertissement" : 'le rappel';
  if (!confirm(`Envoyer ${what} par mail à ${pilots.length} pilote(s) ?\n\n${pilots.map(p => '• ' + p.label).join('\n')}`)) return;
  ccmPostReminders({ level, pilots: pilots.map(p => ({ id: p.id, absences: p.streak_type === 'missed' ? p.streak : 0 })) });
}

// Fenêtre d'envoi pour un pilote : choix du type + texte modifiable
let ccmMailPilot = null;
function ccmMailVars(p) {
  const name = (p.label.split(' - ')[1] || '').trim();
  return {
    '{prenom}': name.split(/[\s.]+/)[0] || name,
    '{callsign}': p.label.split(' - ')[0],
    '{nb_absences}': p.streak_type === 'missed' ? p.streak : p.no_answer,
  };
}
function ccmFillMail(level) {
  const tpl = (CCM_STATS.mails || {})[level] || { subject: '', body: '' };
  const vars = ccmMailVars(ccmMailPilot);
  const fill = t => Object.entries(vars).reduce((acc, [k, v]) => acc.split(k).join(v), t || '');
  document.getElementById('ccm-mail-subject').value = fill(tpl.subject);
  document.getElementById('ccm-mail-body').value = fill(tpl.body);
  document.querySelectorAll('.ccm-mail-type').forEach(b => b.classList.toggle('active', b.dataset.level === level));
  document.getElementById('ccm-mail-modal').dataset.level = level;
  document.getElementById('ccm-mail-excl-note').style.display = level === 'exclusion' ? 'block' : 'none';
}
function ccmOpenMailModal(userId) {
  ccmMailPilot = CCM_STATS.by_pilot.find(x => x.id === userId);
  if (!ccmMailPilot) return;
  const p = ccmMailPilot;
  const mc = p.mail_counts || {};
  const hist = Object.keys(CCM_LEVELS).map(k => `${CCM_LEVELS[k].mailLabel} : ${mc[k] || 0}`).join(' · ');
  const streak = p.streak_type === 'missed' ? `absent depuis ${p.streak} évènement(s)` : (p.streak_type === 'going' ? `${p.streak} participation(s) de suite` : 'pas de série');
  document.getElementById('ccm-mail-pilot').innerHTML = `<b>${ccmEsc(p.label)}</b> — ${streak}<div class="text-muted" style="font-size:11px;">Mails déjà envoyés : ${hist}${p.last_mail ? ' (dernier le ' + ccmFrDate(p.last_mail.date) + ')' : ''}</div>`;
  ccmFillMail(p.level || 'reminder');
  document.getElementById('ccm-mail-modal').style.display = 'flex';
}
function ccmCloseMailModal() { document.getElementById('ccm-mail-modal').style.display = 'none'; }
function ccmSubmitMail() {
  const p = ccmMailPilot;
  const level = document.getElementById('ccm-mail-modal').dataset.level;
  const subject = document.getElementById('ccm-mail-subject').value.trim();
  const body = document.getElementById('ccm-mail-body').value.trim();
  if (!subject || !body) { alert("L'objet et le texte du mail sont obligatoires."); return; }
  if (!confirm(`Envoyer ce mail (${CCM_LEVELS[level].mailLabel}) à ${p.label} ?`)) return;
  const btn = document.getElementById('ccm-mail-send');
  btn.disabled = true;
  ccmPostReminders({ level, subject, body, pilots: [{ id: p.id, absences: p.streak_type === 'missed' ? p.streak : 0 }] })
    .finally(() => { btn.disabled = false; ccmCloseMailModal(); });
}

function ccmSortEvents(key) {
  ccmEventSort = { key, dir: ccmEventSort.key === key ? -ccmEventSort.dir : 1 };
  ccmRenderStats();
}
function ccmSortPilots(key) {
  ccmPilotSort = { key, dir: ccmPilotSort.key === key ? -ccmPilotSort.dir : 1 };
  ccmRenderStats();
}

function ccmToggleEventDetail(eventId, scroll = false) {
  const row = document.getElementById('ccm-ev-detail-' + eventId);
  if (!row) return;
  if (row.style.display !== 'none' && !scroll) { row.style.display = 'none'; return; }

  const e = CCM_STATS.events.find(x => x.id === eventId);
  const names = ids => ids.map(id => CCM_STATS.pilots[id]).filter(Boolean).sort((a, b) => ccmCmp(a, b));

  const col = (title, color, list) => `<div style="flex:1; min-width:200px;">
      <strong style="color:${color};">${title} (${list.length})</strong>
      ${list.length ? '<ul>' + list.map(n => `<li>${ccmEsc(n)}</li>`).join('') + '</ul>' : '<div class="text-muted">—</div>'}
    </div>`;

  row.querySelector('td').innerHTML = `<div style="display:flex; gap:16px; flex-wrap:wrap;">
      ${col('Participent', '#10B981', names(e.going))}
      ${col('Déclinent', '#D42B2B', names(e.declined))}
      ${col('Sans réponse', '#6B7280', names(e.no_answer))}
      ${col('Absents', '#9CA3AF', names(e.absent || []))}
    </div>`;
  row.style.display = '';
  if (scroll) row.scrollIntoView({ behavior: 'smooth', block: 'center' });
}

// Export CSV de la vue affichée (séparateur ; pour Excel en français)
function ccmExportCsv() {
  const d = CCM_STATS;
  const cell = v => '"' + String(v ?? '').replace(/"/g, '""') + '"';
  let rows, name;

  if (ccmStatsView === 'events') {
    name = 'stats_evenements.csv';
    rows = [['Évènement', 'Date', 'Type', 'Statut', 'Concernés', 'Participent', 'Déclinent', 'Sans réponse', 'Taux (%)']];
    d.events.forEach(e => rows.push([
      e.title, e.date, e.is_flight ? 'Vol' : 'Hors vol',
      e.deleted ? 'Supprimé' : (e.cancelled ? 'Annulé' : 'Actif'),
      e.eligible, e.going.length, e.declined.length, e.no_answer.length, e.rate,
    ]));
  } else {
    name = 'stats_pilotes.csv';
    rows = [['Pilote', 'Inscrit le', 'Concerné', 'Participé', 'Décliné', 'Sans réponse', 'Absent', 'Taux de participation (%)', 'Série', 'Alerte', 'Dernier évènement', 'Date']];
    d.by_pilot.forEach(p => rows.push([
      p.label, p.joined, p.eligible, p.going, p.declined, p.no_answer, p.absent, p.rate,
      p.streak_type === 'going' ? p.streak + ' de suite' : (p.streak_type === 'missed' ? 'absent depuis ' + p.streak : ''),
      p.level ? CCM_LEVELS[p.level].label.replace(/^\S+\s/, '') : '',
      p.last_title, p.last_date ? p.last_date.split('-').reverse().join('/') : '',
    ]));
  }

  const csv = '﻿' + rows.map(r => r.map(cell).join(';')).join('\r\n');
  const a = document.createElement('a');
  a.href = URL.createObjectURL(new Blob([csv], { type: 'text/csv;charset=utf-8' }));
  a.download = name;
  a.click();
  URL.revokeObjectURL(a.href);
}

ccmRenderCalendar();
</script>
@endsection