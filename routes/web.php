<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

Route::get('/', function () {
    return redirect('/login');
});

Route::get('/login', function () {
    return view('login');
})->name('login');

Route::get('/dashboard', function () {
    return view('dashboard');
})->name('dashboard');

Route::get('/sso/login', function (Request $request) {
    $redirect = $request->query('redirect', 'http://localhost:5174/sso/callback'); // fallback AMS
    $feSphereLogin = env('FE_SPHERE_LOGIN_URL', 'http://localhost:5173/signin');
    return redirect()->away($feSphereLogin.'?redirect='.urlencode($redirect));
});
