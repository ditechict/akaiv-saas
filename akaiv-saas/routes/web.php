<?php

use App\Http\Controllers\DocumentAgentController;
use App\Http\Controllers\PreviewDocumentController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/admin');
});

Route::middleware('auth')->get('/dev/tokens', function () {
    abort_unless(app()->environment(['local', 'testing']), 404);

    abort_unless(auth()->user()?->hasRole('Platform SuperAdmin'), 403);

    return view('dev.tokens');
})->name('dev.tokens');

Route::middleware(['auth', 'throttle:30,1'])->post(
    '/documents/{document}/analyze',
    [DocumentAgentController::class, 'analyze'],
)->name('documents.analyze');

Route::middleware(['signed', 'auth', 'throttle:60,1'])->get(
    '/documents/{document}/preview',
    PreviewDocumentController::class,
)->name('documents.preview');
