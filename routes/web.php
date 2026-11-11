<?php
use App\Http\Controllers\BukuController;
use Illuminate\Support\Facades\Route;

Route::get('/buku', [BukuController::class, 'index']);
Route::get('/buku/{kode}', [BukuController::class, 'show']);
Route::get('/', function () {
    return view('welcome');
});
