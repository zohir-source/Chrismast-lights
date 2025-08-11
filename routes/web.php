<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ChristmasLightsController;

Route::get('/', [ChristmasLightsController::class, 'index'])->name('lights.index');
