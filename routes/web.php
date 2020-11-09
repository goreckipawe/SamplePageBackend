<?php

use App\Http\Controllers\SamplePage;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('getAgenda', 'App\Http\Controllers\SamplePage@agenda');
Route::post('agendaUserAdd', 'App\Http\Controllers\SamplePage@agendaUserAdd');
Route::get('agendaUserGet', 'App\Http\Controllers\SamplePage@agendaUserGet');