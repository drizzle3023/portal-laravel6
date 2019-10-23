<?php

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

Route::get('/', 'AdminController@index');
Route::get('/login', 'AdminController@index');
Route::post('/login', 'AdminController@doLogin');
Route::get('/logout', 'AdminController@logout');
Route::get('/forgot-password', 'AdminController@showForgotPasswordPage');
Route::post('/reset-password', 'AdminController@resetPassword');
Route::get('locale/{locale?}', 'AdminController@setLocale');

Route::middleware('user-auth')->group(function (){
    Route::get('/dashboard', 'AdminController@dashboard');
    Route::get('/profile', 'AdminController@showProfilePage');
    Route::post('/profile/edit', 'AdminController@editProfile');

    Route::prefix('products')->group(function () {
        Route::get('/', 'AdminController@showProductsPage');
    });

    Route::prefix('domains')->group(function () {
        Route::get('/', 'AdminController@showDomainPage');
        Route::get('/check/{id}', 'AdminController@checkDomain');
    });

    Route::prefix('statistics')->group(function () {
        Route::get('/', 'AdminController@showStatisticsPage');
    });

    Route::prefix('search')->group(function () {
        Route::get('/', 'AdminController@showSearchPage');
    });

    Route::prefix('whitelist')->group(function () {
        Route::get('/', 'AdminController@showWhitelistPage');
        Route::get('/add', 'AdminController@showAddWhitelistPage');
        Route::post('/add', 'AdminController@addWhitelist');
        Route::get('/edit/{id}', 'AdminController@showEditWhitelistPage');
        Route::post('/edit', 'AdminController@editWhitelist');
        Route::post('/delete', 'AdminController@deleteWhitelist');
        Route::post('/toggle-enable', 'AdminController@toggleWhitelistEnable');
    });

    Route::prefix('blacklist')->group(function () {
        Route::get('/', 'AdminController@showBlacklistPage');
        Route::get('/add', 'AdminController@showAddBlacklistPage');
        Route::post('/add', 'AdminController@addBlacklist');
        Route::get('/edit/{id}', 'AdminController@showEditBlacklistPage');
        Route::post('/edit', 'AdminController@editBlacklist');
        Route::post('/delete', 'AdminController@deleteBlacklist');
        Route::post('/toggle-enable', 'AdminController@toggleBlacklistEnable');
    });

});

Route::middleware('sales-auth')->group(function (){
    Route::prefix('customer')->group(function () {

        Route::get('/', 'AdminController@showCustomerListPage');
        Route::get('/add', 'AdminController@showCustomerAddPage');
        Route::post('/add', 'AdminController@addCustomer');
        Route::get('/edit/{id}', 'AdminController@showCustomerEditPage');
        Route::post('/edit', 'AdminController@editCustomer');
        Route::post('/delete', 'AdminController@deleteCustomer');
    });

    Route::prefix('domains')->group(function () {
        Route::get('/{id}', 'AdminController@showDomainPage');
        Route::get('/{id}/add', 'AdminController@showDomainAddPage');
        Route::post('/add', 'AdminController@addDomain');
        Route::get('/edit/{id}', 'AdminController@showDomainEditPage');
        Route::post('/edit', 'AdminController@editDomain');
        Route::post('/delete', 'AdminController@deleteDomain');
    });

    Route::prefix('products')->group(function () {
        Route::get('/{id}', 'AdminController@showProductsPage');
        Route::get('/{id}/add', 'AdminController@showProductAddPage');
        Route::post('/add', 'AdminController@addProduct');
        Route::get('/edit/{id}', 'AdminController@showProductEditPage');
        Route::post('/edit', 'AdminController@editProduct');
        Route::post('/delete', 'AdminController@deleteProduct');
    });

});


Route::middleware('admin-auth')->group(function (){
    Route::prefix('salesperson')->group(function () {

        Route::get('/', 'AdminController@showSalespersonPage');
        Route::get('/add', 'AdminController@showSalespersonAddPage');
        Route::post('/add', 'AdminController@addSalesperson');
        Route::get('/edit/{id}', 'AdminController@showSalespersonEditPage');
        Route::post('/edit', 'AdminController@editSalesperson');
        Route::post('/delete', 'AdminController@delSalesperson');
    });
});
