<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::group(['prefix' => 'food', 'middleware' => ['api']], function () {
    Route::get('/search', 'FoodController@search')->name('food.api.search');
    Route::get('/detail/{slug}', 'FoodController@detail')->name('food.api.detail');
    Route::get('/list', 'FoodController@index')->name('food.api.list');
    Route::get('/min-max-price', 'FoodController@getMinMaxPrice')->name('food.api.min_max_price');
    Route::get('/filters', 'FoodController@getFiltersSearch')->name('food.api.filters');
});
