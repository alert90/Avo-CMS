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
    Route::post('/{code}/scan', 'BookingController@recordScan')->name('api.booking.scan');
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

/* Vendor API Routes - Protected */
Route::group(['prefix'=>'vendor','middleware' => 'auth:sanctum'],function(){

    // Dashboard
    Route::get('dashboard', [\Modules\Api\Controllers\Vendor\VendorController::class, 'dashboard'])->name("api.vendor.dashboard");

    // Services Management
    Route::get('services', [\Modules\Api\Controllers\Vendor\VendorController::class, 'services'])->name("api.vendor.services");
    Route::get('services/{type}/{id}', [\Modules\Api\Controllers\Vendor\VendorController::class, 'serviceDetail'])->name("api.vendor.service.detail");
    Route::post('services/{type}', [\Modules\Api\Controllers\Vendor\VendorController::class, 'createService'])->name("api.vendor.service.create");
    Route::put('services/{type}/{id}', [\Modules\Api\Controllers\Vendor\VendorController::class, 'updateService'])->name("api.vendor.service.update");
    Route::delete('services/{type}/{id}', [\Modules\Api\Controllers\Vendor\VendorController::class, 'deleteService'])->name("api.vendor.service.delete");
    Route::post('services/{type}/{id}/bulk', [\Modules\Api\Controllers\Vendor\VendorController::class, 'bulkEditService'])->name("api.vendor.service.bulk");

    // Bookings Management
    Route::get('bookings', [\Modules\Api\Controllers\Vendor\VendorController::class, 'bookings'])->name("api.vendor.bookings");
    Route::get('bookings/{id}', [\Modules\Api\Controllers\Vendor\VendorController::class, 'bookingDetail'])->name("api.vendor.booking.detail");
    Route::put('bookings/{id}/status', [\Modules\Api\Controllers\Vendor\VendorController::class, 'updateBookingStatus'])->name("api.vendor.booking.update_status");

    // Enquiries Management
    Route::get('enquiries', [\Modules\Api\Controllers\Vendor\VendorController::class, 'enquiries'])->name("api.vendor.enquiries");
    Route::post('enquiries/{id}/reply', [\Modules\Api\Controllers\Vendor\VendorController::class, 'replyEnquiry'])->name("api.vendor.enquiry.reply");

    // News Management
    Route::get('news', [\Modules\Api\Controllers\Vendor\VendorController::class, 'news'])->name("api.vendor.news");
    Route::post('news', [\Modules\Api\Controllers\Vendor\VendorController::class, 'createNews'])->name("api.vendor.news.create");
    Route::put('news/{id}', [\Modules\Api\Controllers\Vendor\VendorController::class, 'updateNews'])->name("api.vendor.news.update");
    Route::delete('news/{id}', [\Modules\Api\Controllers\Vendor\VendorController::class, 'deleteNews'])->name("api.vendor.news.delete");

    // Verification
    Route::get('verification', [\Modules\Api\Controllers\Vendor\VendorController::class, 'verification'])->name("api.vendor.verification");
    Route::post('verification', [\Modules\Api\Controllers\Vendor\VendorController::class, 'submitVerification'])->name("api.vendor.verification.submit");

    // Payout Management
    Route::get('payouts', [\Modules\Api\Controllers\Vendor\VendorController::class, 'payouts'])->name("api.vendor.payouts");
    Route::post('payouts/request', [\Modules\Api\Controllers\Vendor\VendorController::class, 'createPayoutRequest'])->name("api.vendor.payouts.request");
    Route::get('payouts/methods', [\Modules\Api\Controllers\Vendor\VendorController::class, 'payoutMethods'])->name("api.vendor.payouts.methods");
    Route::post('payouts/methods', [\Modules\Api\Controllers\Vendor\VendorController::class, 'setPayoutMethod'])->name("api.vendor.payouts.methods.set");

    // Availability Management
    Route::get('availability/{type}/{id}', [\Modules\Api\Controllers\Vendor\VendorController::class, 'getAvailability'])->name("api.vendor.availability");
    Route::post('availability/{type}/{id}', [\Modules\Api\Controllers\Vendor\VendorController::class, 'updateAvailability'])->name("api.vendor.availability.update");

});
