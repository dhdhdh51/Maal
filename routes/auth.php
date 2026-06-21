<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Authentication Routes (web)
|--------------------------------------------------------------------------
|
| Registration, login, OTP verification, password reset and the age gate.
| Controllers are wired up in the Authentication system.
|
*/

// Routes are registered by the Authentication system (System #2).
Route::view('/__auth_placeholder', 'welcome')->name('auth.placeholder');
