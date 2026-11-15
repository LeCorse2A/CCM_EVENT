<?php

use Illuminate\Support\Facades\Route;

Route::get('/', 'PublicController@index')->name('index');

// ── Participation ──
Route::post('/{id}/participate', 'PublicController@participate')->name('participate');
Route::post('/{id}/decline', 'PublicController@decline')->name('decline');
Route::get('/{id}/participants', 'PublicController@participants')->name('participants');

// ── Legs (participation par leg) ──
Route::get('/{id}/legs', 'PublicController@legs')->name('legs.index');
Route::get('/{id}/leg-participants', 'PublicController@legParticipants')->name('legs.participants');

// ── Réservation (aéroport + stand + avion) ──
Route::get('/stands/{icao}', 'PublicController@stands')->name('stands');
Route::post('/{id}/reservations', 'PublicController@storeReservation')->name('reservations.store');
Route::get('/{id}/reservations', 'PublicController@myReservation')->name('reservations.mine');
Route::get('/{id}/all-reservations', 'PublicController@allReservations')->name('reservations.all');
Route::post('/reservations/{reservation_id}/cancel', 'PublicController@cancelReservation')->name('reservations.cancel');
Route::get('/{id}/my-aircraft', 'PublicController@myAircraft')->name('myaircraft');

// ── AJOUT : page dédiée "planifier mon vol événement", par leg ──
Route::get('/{id}/freeflight/{leg}', 'EventFreeFlightController@show');