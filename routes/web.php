<?php

use Illuminate\Support\Facades\Route;

// Redirigir la raíz directamente al panel institucional de ATLAS TECNM
Route::get('/', function () {
    return redirect('/admin');
});
