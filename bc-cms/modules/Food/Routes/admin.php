<?php
use \Illuminate\Support\Facades\Route;
Route::get('/','FoodController@index')->name('food.admin.index');
Route::get('/create','FoodController@create')->name('food.admin.create');
Route::get('/edit/{id}','FoodController@edit')->name('food.admin.edit');
Route::post('/store/{id}','FoodController@store')->name('food.admin.store');
Route::post('/bulkEdit','FoodController@bulkEdit')->name('food.admin.bulkEdit');
Route::get('/recovery','FoodController@recovery')->name('food.admin.recovery');
Route::get('/getForSelect2','FoodController@getForSelect2')->name('food.admin.getForSelect2');
Route::get('/getForSelect2','FoodController@getForSelect2')->name('food.admin.getForSelect2');


Route::group(['prefix'=>'attribute'],function (){
    Route::get('/','AttributeController@index')->name('food.admin.attribute.index');
    Route::get('edit/{id}','AttributeController@edit')->name('food.admin.attribute.edit');
    Route::post('store/{id}','AttributeController@store')->name('food.admin.attribute.store');
    Route::post('/editAttrBulk','AttributeController@editAttrBulk')->name('food.admin.attribute.editAttrBulk');

    Route::get('terms/{id}','AttributeController@terms')->name('food.admin.attribute.term.index');
    Route::get('term_edit/{id}','AttributeController@term_edit')->name('food.admin.attribute.term.edit');
    Route::post('term_store','AttributeController@term_store')->name('food.admin.attribute.term.store');
    Route::post('/editTermBulk','AttributeController@editTermBulk')->name('food.admin.attribute.term.editTermBulk');

    Route::get('getForSelect2','AttributeController@getForSelect2')->name('food.admin.attribute.term.getForSelect2');
});

Route::group(['prefix'=>'availability'],function(){
    Route::get('/','AvailabilityController@index')->name('food.admin.availability.index');
    Route::get('/loadDates','AvailabilityController@loadDates')->name('food.admin.availability.loadDates');
    Route::post('/store','AvailabilityController@store')->name('food.admin.availability.store');
});
