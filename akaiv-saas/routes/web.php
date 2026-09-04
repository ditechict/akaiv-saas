<?php

use App\Http\Controllers\DocumentAgentController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/admin');
});

Route::middleware(['auth', 'throttle:30,1'])->post(
    '/documents/{document}/analyze',
    [DocumentAgentController::class, 'analyze'],
)->name('documents.analyze');
