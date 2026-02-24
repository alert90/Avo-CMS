<?php

use Illuminate\Http\Request;
use \Illuminate\Support\Facades\Route;
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
/* Config */
Route::get('configs','BookingController@getConfigs')->name('api.get_configs');

Route::get('test-public', function() {
    return response()->json([
        'status' => 1,
        'message' => 'This is a public route',
        'data' => ['test' => 'success']
    ]);
});


/* Service */
Route::get('services','SearchController@searchServices')->name('api.service-search');
Route::get('{type}/search','SearchController@search')->name('api.search2');
Route::get('{type}/detail/{id}','SearchController@detail')->name('api.detail');
Route::get('{type}/availability/{id}','SearchController@checkAvailability')->name('api.service.check_availability');
Route::get('boat/availability-booking/{id}','SearchController@checkBoatAvailability')->name('api.service.checkBoatAvailability');

Route::get('{type}/filters','SearchController@getFilters')->name('api.service.filter');
Route::get('{type}/form-search','SearchController@getFormSearch')->name('api.service.form');

/* Public Authors */
Route::get('authors', [\Modules\Api\Controllers\AuthorController::class, 'getAuthorsPublic'])->name('api.authors.public');
Route::get('authors/{id}', [\Modules\Api\Controllers\AuthorController::class, 'getAuthorDetailPublic'])->name('api.author.detail.public');

Route::group(['middleware' => 'api'],function(){
    Route::post('{type}/write-review/{id}','ReviewController@writeReview')->name('api.service.write_review');
});


/* HomePage */
Route::get('home-page','BookingController@getHomeLayout')->name('api.get_home_layout');
Route::post('forgot-password','AuthController@forgotPassword')->name('api.forgot-password');
Route::post('reset-password','AuthController@resetPassword')->name('api.reset-password');

/* Register - Login */
Route::group(['middleware' => 'api', 'prefix' => 'auth'], function ($router) {
    Route::post('login', 'AuthController@login')->middleware(['throttle:login']);
    Route::post('register', 'AuthController@register');
    Route::post('logout', 'AuthController@logout');
    Route::post('refresh', 'AuthController@refresh');
    Route::get('me', 'AuthController@me');
    Route::post('me', 'AuthController@updateUser');
    Route::post('change-password', 'AuthController@changePassword');
    Route::get('/wishlist','UserController@indexWishlist')->name("api.user.wishList.index");

});

/* User */
Route::group(['prefix' => 'user', 'middleware' => ['auth:sanctum']], function ($router) {
    Route::get('booking-history', 'UserController@getBookingHistory')->name("api.user.booking_history");
    Route::post('/wishlist/{object_model}/{object_id}', 'UserController@addWishList')->name("api.user.wishList.add");
    Route::delete('/wishlist/{object_model}/{object_id}', 'UserController@deleteWishList')->name("api.user.wishList.delete");
    Route::get('/wishlist','UserController@indexWishlist')->name("api.user.wishList.index");
    Route::post('/permanently_delete','UserController@permanentlyDelete')->name("user.permanently.delete");
    Route::get('vendor/dashboard', 'UserController@getVendorDashboard')->name("api.vendor.dashboard");
    Route::post('vendor/reload-chart', 'UserController@reloadVendorChart')->name("api.vendor.reload_chart");
    Route::post('update-activity', [\Modules\Api\Controllers\UserStatusController::class, 'updateActivity']);
    Route::post('check-online-status', [\Modules\Api\Controllers\UserStatusController::class, 'checkOnlineStatus']);
});

/* Messages - Protected */
Route::group(['prefix' => 'user', 'middleware' => ['auth:sanctum']], function ($router) {
    Route::get('conversations', [\Modules\Api\Controllers\MessageController::class, 'getConversations'])->name("api.user.conversations");
    Route::get('conversations/{id}', [\Modules\Api\Controllers\MessageController::class, 'getConversation'])->name("api.user.conversation");
    Route::post('conversations/{id}/send', [\Modules\Api\Controllers\MessageController::class, 'sendMessage'])->name("api.user.conversation.send");
    Route::post('conversations/{id}/read', [\Modules\Api\Controllers\MessageController::class, 'markAsRead'])->name("api.user.conversation.read");
});

/* Notification - Protected */
Route::group(['prefix' => 'user', 'middleware' => ['auth:sanctum']], function ($router) {
    Route::get('notifications', [\Modules\Api\Controllers\NotificationController::class, 'index'])->name("api.user.notifications");
    Route::post('notifications/{id}/read', [\Modules\Api\Controllers\NotificationController::class, 'markAsRead'])->name("api.user.notifications.read");
    Route::post('notifications/read-all', [\Modules\Api\Controllers\NotificationController::class, 'markAllAsRead'])->name("api.user.notifications.read_all");
});

/* Location */
Route::get('locations','LocationController@search')->name('api.location.search');
Route::get('location/{id}','LocationController@detail')->name('api.location.detail');

/* Booking */
Route::group(['prefix'=>config('booking.booking_route_prefix')],function(){
    Route::post('/addToCart','BookingController@addToCart')->name("api.booking.add_to_cart");
    Route::post('/addEnquiry','BookingController@addEnquiry')->name("api.booking.add_enquiry");
    Route::post('/doCheckout','BookingController@doCheckout')->name('api.booking.doCheckout');
    Route::get('/confirm/{gateway}','BookingController@confirmPayment');
    Route::get('/cancel/{gateway}','BookingController@cancelPayment');
    Route::get('/{code}','BookingController@detail');
    Route::get('/{code}/thankyou','BookingController@thankyou')->name('booking.thankyou');
    Route::get('/{code}/checkout','BookingController@checkout');
    Route::get('/{code}/check-status','BookingController@checkStatusCheckout');
});

/* Gateways */
Route::get('/gateways','BookingController@getGatewaysForApi');

/* News */
Route::get('news','NewsController@search')->name('api.news.search');
Route::get('news/category','NewsController@category')->name('api.news.category');
Route::get('news/{id}','NewsController@detail')->name('api.news.detail');

/* Language & Currency */
Route::post('set-language', 'SettingController@setLanguagePost')->name('api.set-language-post');
Route::post('set-currency', 'SettingController@setCurrencyPost')->name('api.set-currency-post');
Route::get('current-settings', 'SettingController@getCurrentSettings')->name('api.current-settings');

/* Media */
Route::group(['prefix'=>'media','middleware' => 'auth:sanctum'],function(){
    Route::post('/store','MediaController@store')->name("api.media.store");
});
