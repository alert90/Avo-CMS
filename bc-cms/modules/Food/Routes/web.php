<?php
use \Illuminate\Support\Facades\Route;

Route::group(['prefix'=>env('FOOD_ROUTE_PREFIX','food')],function(){
    Route::get('/','FoodController@index')->name('food.search'); // Search
    Route::get('/{slug}','FoodController@detail')->name('food.detail');// Detail
});

Route::group(['prefix'=>'user/'.env('FOOD_ROUTE_PREFIX','food'),'middleware' => ['auth','verified']],function(){
    Route::get('/','VendorFoodController@indexFood')->name('food.vendor.index');
    Route::get('/create','VendorFoodController@createFood')->name('food.vendor.create');
    Route::get('/edit/{id}','VendorFoodController@editFood')->name('food.vendor.edit');
    Route::get('/del/{id}','VendorFoodController@deleteFood')->name('food.vendor.delete');
    Route::post('/store/{id}','VendorFoodController@store')->name('food.vendor.store');
    Route::get('bulkEdit/{id}','VendorFoodController@bulkEditFood')->name("food.vendor.bulk_edit");
    Route::get('/booking-report/bulkEdit/{id}','VendorFoodController@bookingReportBulkEdit')->name("food.vendor.booking_report.bulk_edit");
    Route::get('/recovery','VendorFoodController@recovery')->name('food.vendor.recovery');
    Route::get('/restore/{id}','VendorFoodController@restore')->name('food.vendor.restore');
});

Route::group(['prefix'=>'user/'.env('FOOD_ROUTE_PREFIX','food')],function(){
    Route::group(['prefix'=>'availability'],function(){
        Route::get('/','AvailabilityController@index')->name('food.vendor.availability.index');
        Route::get('/loadDates','AvailabilityController@loadDates')->name('food.vendor.availability.loadDates');
        Route::post('/store','AvailabilityController@store')->name('food.vendor.availability.store');
    });
});
