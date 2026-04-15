<?php

use Illuminate\Foundation\Http\Kernel;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});
