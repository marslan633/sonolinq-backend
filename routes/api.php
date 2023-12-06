<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\Manager\ClientController;
use App\Http\Controllers\Api\Manager\StaffController;
use App\Http\Controllers\Api\Manager\ServiceCategoryController;
use App\Http\Controllers\Api\Manager\PackageController;
use App\Http\Controllers\Api\Client\TableController;
use App\Http\Controllers\Api\Manager\ServiceController;
use App\Http\Controllers\Api\Client\VariantController;
use App\Http\Controllers\Api\Client\DealController;
use App\Http\Controllers\Api\Client\ShopConfigurationController;
use App\Http\Controllers\Api\Client\BankInfoController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

/*Auth Routes*/
Route::prefix('auth')->controller(AuthController::class)->group(function () {
    Route::post('login', 'login');
    Route::post('register', 'register');
    Route::post('check-email', 'check_email');
    Route::get('verification-code/{token}', 'verification_code');
    Route::post('forgot-password', 'forgot_password');
});

/**
 * Defining User Routes
 * **/
Route::group(['prefix' => 'manager', 'middleware' => ['auth:user-api', 'scopes:user']], function () {
    /*Change Password Api*/
    Route::prefix('auth')->controller(AuthController::class)->group(function () {
        Route::post('change-password', 'change_password');
    });
    Route::apiResources([
        'staff' => StaffController::class,
        'client' => ClientController::class,
        'service-category' => ServiceCategoryController::class,
        'service' => ServiceController::class,
        'package' => PackageController::class,
    ]);
});


/**
 * Defining Client Routes
 * **/
Route::group(['prefix' => 'client', 'middleware' => ['auth:client-api', 'scopes:client']], function () {
    /*Change Password Api*/
    Route::prefix('auth')->controller(AuthController::class)->group(function () {
        Route::post('change-password', 'change_password');
    });

    /*Defining Client Routes*/
    Route::prefix('dashboard')->controller(DashboardController::class)->group(function () {
        Route::get('client-profile', 'client_profile');
    });

    /*Defining Supliment Routes for Client*/
    Route::prefix('client')->controller(ClientController::class)->group(function () {
        Route::get('statements', 'client_statements');
        Route::get('get-configurations', 'get_configurations');
        Route::post('update-configurations', 'update_configurations');
    });


    Route::apiResources([
        'table' => TableController::class,       
        'variant' => VariantController::class,
        'deals' => DealController::class,
        'shop-configuration' => ShopConfigurationController::class,
        'bank-info' => BankInfoController::class,
    ]);
    
    /*Defining shope configuration for Client*/
    Route::post('/update-configuration', [ShopConfigurationController::class, 'updateConfiguration']);
    Route::get('/show-configuration', [ShopConfigurationController::class, 'showConfiguration']);
});


/**
 * Public Routes
 * **/
Route::get('get-configuration/{id}', [ShopConfigurationController::class, 'getConfiguration']);
// Get Services for Clients
Route::get('/get-services', [ServiceController::class, 'getServices']); 