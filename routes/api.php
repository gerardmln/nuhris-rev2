<?php

use App\Http\Controllers\Api\AcademicCalendarController;
use Illuminate\Support\Facades\Route;

Route::get('/academic-calendar', [AcademicCalendarController::class, 'index'])
    ->name('api.academic-calendar.index');
