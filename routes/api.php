<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\Manager\ClientController;
use App\Http\Controllers\Api\Manager\StaffController;
use App\Http\Controllers\Api\Manager\ServiceCategoryController;
use App\Http\Controllers\Api\Manager\PackageController;
use App\Http\Controllers\Api\Manager\ServiceController;
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
        Route::get('logout', 'logout');
    });
    Route::apiResources([
        'staff' => StaffController::class,
        'client' => ClientController::class,
        'service-category' => ServiceCategoryController::class,
        'service' => ServiceController::class,
        'package' => PackageController::class,
    ]);


    // get All booking for admin
    Route::get('/get-booking-list', [ClientController::class, 'bookingList']); 
    Route::get('/dashboard-stats', [ClientController::class, 'adminStats']); 
});


/**
 * Defining Client Routes
 * **/
Route::group(['prefix' => 'client', 'middleware' => ['auth:client-api', 'scopes:client']], function () {
    /*Change Password Api*/
    Route::prefix('auth')->controller(AuthController::class)->group(function () {
        Route::post('change-password', 'change_password');
        Route::get('logout', 'logout');
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
        'bank-info' => BankInfoController::class,
    ]);

    Route::get('/get-client', [ClientController::class, 'getClient']);
    Route::patch('/update-client', [ClientController::class, 'updateClient']);

    // Sonographer eligibility check API
    
    Route::post('/appointment', [ClientController::class, 'appointment']);
    Route::get('/get-eligible-sonographer', [ClientController::class, 'getEligibleSonographers']);
    Route::get('/accept-booking-request/{id}', [ClientController::class, 'acceptBookingRequest']);
    Route::get('/reject-booking-request/{id}', [ClientController::class, 'rejectBookingRequest']);

    Route::get('/get-doctor-bookings', [ClientController::class, 'getDoctorBookings']);
    
    Route::get('/validate-token', [ClientController::class, 'validateToken']);

    // Doctor complete their appointment (booking) in progress route
    Route::get('/completed-booking-request/{id}', [ClientController::class, 'completedBookingRequest']);

    Route::patch('/update-booking-status/{id}', [ClientController::class, 'updateBookingStatus']);

    Route::get('/dashboard-stats', [ClientController::class, 'clientStats']); 
}); 


/**
 * Public Routes
 * **/
Route::get('/get-services', [ServiceController::class, 'getServices']); 
Route::get('/get-service-categories', [ServiceCategoryController::class, 'getServiceCategories']); 
Route::post('/sonographer-eligibility', [ClientController::class, 'checkEligibility']);
Route::get('/show-booking/{id}', [ClientController::class, 'showBooking']);