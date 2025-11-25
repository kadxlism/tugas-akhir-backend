<?php

use Illuminate\Support\Facades\Route;

Route::get('/{any}', function () {
    return view('app');
})->where('any', '^(?!build|storage).*$'); // ⬅️ Abaikan folder build & storage (jika ada)

Route::get('/login', function () {
    return response()->json(['message' => 'Unauthorized.'], 401);
})->name('login');
