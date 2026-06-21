<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin Panel Routes
|--------------------------------------------------------------------------
|
| Prefixed with /admin and named admin.* (see bootstrap/app.php). Guarded by
| auth + admin.access permission + admin 2FA. Populated by the Admin system.
|
*/

// Routes are registered by the Admin Panel system (System #9).
Route::view('/__admin_placeholder', 'welcome')->name('placeholder');
