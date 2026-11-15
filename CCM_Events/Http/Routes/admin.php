<?php

use Illuminate\Support\Facades\Route;

// Page unique du module (onglets Calendrier / Vols / Config gérés en JS/Blade)
Route::get('/', 'EventController@index')->name('index');

// ── Onglet "Vols & Réservations" (doit être AVANT /{id} pour ne pas être capturée) ──
Route::get('/flights-data', 'EventController@flightsData')->name('flights.data');

// ── Onglet "Statistiques" (doit aussi être AVANT /{id}) ──
Route::get('/stats-data', 'EventController@statsData')->name('stats.data');
Route::post('/stats/reminders', 'EventController@sendReminders')->name('stats.reminders');

// ── Évènements (calendrier) ──
Route::get('/{id}', 'EventController@show')->name('show');
Route::post('/', 'EventController@store')->name('store');
Route::put('/{id}', 'EventController@update')->name('update');
Route::delete('/{id}', 'EventController@destroy')->name('destroy');

// ── Legs ──
Route::get('/{id}/legs', 'EventController@getLegs')->name('legs.index');
Route::post('/{id}/legs', 'EventController@storeLeg')->name('legs.store');
Route::delete('/{id}/legs/{leg_id}', 'EventController@destroyLeg')->name('legs.destroy');

// ── Vols liés à l'évènement (activation, positionnement avion) ──
Route::post('/{id}/flight/toggle', 'EventFlightController@toggle')->name('flight.toggle');
Route::post('/reservations/{reservation_id}/position', 'EventFlightController@positionAircraft')->name('flight.position');
Route::post('/reservations/{reservation_id}/return', 'EventFlightController@returnAircraft')->name('flight.return');

// ── Config ──
Route::get('/config/data', 'ConfigController@index')->name('config.index');
Route::post('/config/data', 'ConfigController@update')->name('config.update');
Route::post('/config/banner', 'ConfigController@uploadBanner')->name('config.banner');
Route::delete('/config/banner', 'ConfigController@deleteBanner')->name('config.banner.delete');