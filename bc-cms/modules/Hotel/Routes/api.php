<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Hotel API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for the hotel module. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Hotel room availability and selection endpoints
Route::get('hotel/{id}/rooms', [ApiHotelController::class, 'getRooms'])->name('api.hotel.rooms');
Route::post('hotel/{id}/check-availability', [ApiHotelController::class, 'checkRoomAvailability'])->name('api.hotel.check_availability');
