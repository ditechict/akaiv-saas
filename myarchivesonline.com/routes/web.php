<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('welcome', function () {
    return view('auth.login');
});

Auth::routes(['register' => false, 'reset' => true]);

Route::get('/', 'DocumentController@index')->name('index');
Route::get('/home', 'DocumentController@index')->name('home');
Route::get('/my-documents', 'DocumentController@index')->name('documents');

Route::get('/create-folder', 'FolderController@create')->name('createFolder');
Route::post('/create-folder', 'FolderController@store')->name('storeFolder');

Route::get('/create-document', 'DocumentController@create')->name('createDocument');
Route::post('/create-document', 'DocumentController@store')->name('storeDocument');

Route::post('/search-document', 'DocumentController@search')->name('searchDocument');

Route::get('/document/{document}', 'DocumentController@download')->name('downloadDocument');
Route::get('/document/{document}/preview', 'DocumentController@show')->name('showDocument');
Route::delete('/document/{document}', 'DocumentController@destroy')->name('destroyDocument');

Route::get('/edit-document/{document}', 'DocumentController@edit')->name('editDocument');
Route::post('/edit-document', 'DocumentController@update')->name('updateDocument');

Route::get('/create-user', 'UserController@create')->name('createUser');
Route::post('/create-user', 'UserController@store')->name('storeUser');
