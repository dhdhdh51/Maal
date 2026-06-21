<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Public site routes. The cinematic frontend + catalog routes are added by
| later systems; for now a temporary home keeps named routes resolvable.
|
*/

Route::get('/', fn () => view('home-placeholder'))->name('home');
