<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\Manager\ClientController;
use App\Http\Controllers\Api\Client\ClientUserController;
use App\Http\Controllers\Api\Manager\StaffController;
use App\Http\Controllers\Api\Manager\ServiceCategoryController;
use App\Http\Controllers\Api\Manager\PackageController;
use App\Http\Controllers\Api\Manager\ServiceController;
use App\Http\Controllers\Api\Manager\SonogramController;
use App\Http\Controllers\Api\Client\BankInfoController;
use App\Http\Controllers\Api\Manager\FaqController;
use App\Http\Controllers\Api\Manager\LanguageController;
use App\Http\Controllers\Api\Manager\EquipmentController;
use App\Http\Controllers\Api\Manager\SonographerTypeController;
use App\Http\Controllers\Api\Manager\SonographerTimeController;
use App\Http\Controllers\Api\Manager\TermController;
use App\Http\Controllers\Api\Manager\SupportTicketController;
use App\Http\Controllers\Api\Manager\TicketNoteController;
use App\Http\Controllers\Api\Manager\EmailTemplateController;
use App\Http\Controllers\Api\Manager\ManagerController;
use App\Http\Controllers\Api\Client\DoctorController;
use App\Http\Controllers\Api\Client\SonographerController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\NotificationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

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
        'faq' => FaqController::class,
        'language' => LanguageController::class,
        'equipment' => EquipmentController::class,
        'sonographer-type' => SonographerTypeController::class,
        'sonographer-time' => SonographerTimeController::class,
        'term' => TermController::class,
        'support-ticket' => SupportTicketController::class,
        'ticket-note' => TicketNoteController::class,
        'email-template' => EmailTemplateController::class,
        'sonogram' => SonogramController::class,
    ]);

    Route::get('/dashboard-stats', [ManagerController::class, 'adminStats']);
    Route::get('/get-booking-list', [ManagerController::class, 'bookingList']);
    Route::post('client-chart', [ManagerController::class, 'clientChart']);
    Route::post('total-earning-chart', [ManagerController::class, 'totalEarningChart']);
    Route::post('booking-chart', [ManagerController::class, 'bookingChart']);

    /* Defining Reviews Api Routes For Admin */
    Route::get('get-reviews', [ReviewController::class, 'get']);
    Route::delete('delete-review/{id}', [ReviewController::class, 'delete']);

    /* Defining Level System Api Routes For Admin */
    Route::get('get-level-system', [ManagerController::class, 'getLevelSystem']);
    Route::patch('update-level-system/{id}', [ManagerController::class, 'updateLevelSystem']);

    /*Defining Manager Notifications Routes*/
    Route::get('notifications', [NotificationController::class, 'notifications']);
    Route::get('unread-notifications', [NotificationController::class, 'unreadNotifications']);
    Route::patch('read-notifications', [NotificationController::class, 'readManagerNotifications']);
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
    Route::prefix('client')->controller(ClientUserController::class)->group(function () {
        Route::get('statements', 'client_statements');
        Route::get('get-configurations', 'get_configurations');
        Route::post('update-configurations', 'update_configurations');
    });


    Route::apiResources([
        'bank-info' => BankInfoController::class,
    ]);

    /* Defining Booking Routes for Doctor */
    Route::post('/appointment', [DoctorController::class, 'appointment']);
    Route::get('/get-doctor-bookings', [DoctorController::class, 'getDoctorBookings']);
    // Doctor complete their appointment (booking) in progress route
    Route::get('/completed-booking-request/{id}', [DoctorController::class, 'completedBookingRequest']);
    Route::get('/doctor-cancel-booking/{id}', [DoctorController::class, 'doctorCancelBooking']);

    /* Defining Booking Routes for Sonographer */
    Route::get('/get-eligible-sonographer', [SonographerController::class, 'getEligibleSonographers']);
    Route::get('/accept-booking-request/{id}', [SonographerController::class, 'acceptBookingRequest']);
    Route::get('/reject-booking-request/{id}', [SonographerController::class, 'rejectBookingRequest']);
    Route::get('/sonographer-cancel-booking/{id}', [SonographerController::class, 'sonographerCancelBooking']);

    /* Client's API's */
    Route::get('/dashboard-stats', [ClientUserController::class, 'clientStats']);
    Route::post('earning-chart', [ClientUserController::class, 'clientEarningChart']);
    Route::post('booking-chart', [ClientUserController::class, 'clientBookingChart']);
    Route::get('/validate-token', [ClientUserController::class, 'validateToken']);
    Route::patch('/update-booking-status/{id}', [ClientUserController::class, 'updateBookingStatus']);
    Route::post('direct-booking', [ClientUserController::class, 'directBooking']);
    Route::patch('/update-client', [ClientUserController::class, 'updateClient']);
    Route::get('/get-client', [ClientUserController::class, 'getClient']);

    /* Reviews Api For Sonographer and Doctor */
    Route::post('create-review', [ReviewController::class, 'store']);
    Route::patch('update-review/{id}', [ReviewController::class, 'update']);

    Route::post('store-support-ticket', [SupportTicketController::class, 'storeTicket']);
    Route::get('get-support-ticket', [SupportTicketController::class, 'getTicket']);
    Route::get('show-support-ticket/{id}', [SupportTicketController::class, 'showTicket']);
    Route::patch('update-support-ticket/{id}', [SupportTicketController::class, 'updateTicket']);
    Route::delete('delete-support-ticket/{id}', [SupportTicketController::class, 'deleteTicket']);


    Route::post('store-ticket-note', [TicketNoteController::class, 'storeTicketNote']);
    Route::get('get-ticket-note', [TicketNoteController::class, 'getTicketNote']);
    Route::get('show-ticket-note/{id}', [TicketNoteController::class, 'showTicketNote']);
    Route::patch('update-ticket-note/{id}', [TicketNoteController::class, 'updateTicketNote']);
    Route::delete('delete-ticket-note/{id}', [TicketNoteController::class, 'deleteTicketNote']);

    /*Defining Client Notifications Routes*/
    Route::get('get-notifications', [NotificationController::class, 'getNotifications']);
    Route::get('get-unread-notifications', [NotificationController::class, 'getLatestUnreadNotifications']);
    Route::patch('read-notifications', [NotificationController::class, 'readNotifications']);

    /* Account Creation, Transfers and Transactions History Routes*/
    Route::prefix('connect-account')->group(function () {
        Route::post('create', [ClientController::class, 'createConnectAccount']);
        Route::post('verify', [ClientController::class, 'verifyConnectAccount']);
        Route::post('status', [ClientController::class, 'connectAccountVerification']);
    });
    Route::get('connect-accounts', [ClientController::class, 'getConnectAccounts']);
    Route::post('withdrawal-amount', [ClientController::class, 'withdrawalAmount']);
    Route::get('transactions', [ClientController::class, 'transactionsHistory']);
});
Route::post('/send-web-notification', [ClientController::class, 'sendNotification']);

/**
 * Public Routes
 * **/
Route::get('/get-services', [ServiceController::class, 'getServices']);
Route::get('/get-service-categories', [ServiceCategoryController::class, 'getServiceCategories']);
Route::get('/get-sonograms', [SonogramController::class, 'getSonogram']);
Route::post('/sonographer-eligibility', [DoctorController::class, 'checkEligibility']);
Route::get('/show-booking/{id}', [DoctorController::class, 'showBooking']);
Route::get('/get-faqs', [FaqController::class, 'getFaqs']);
Route::get('/get-languages', [LanguageController::class, 'getLanguages']);
Route::get('/get-equipment', [EquipmentController::class, 'getLanguages']);
Route::get('/get-sonographer-types', [SonographerTypeController::class, 'getSonographerTypes']);
Route::get('/get-sonographer-time', [SonographerTimeController::class, 'getSonographerTime']);
Route::get('/get-term', [TermController::class, 'getTerm']);
Route::get('/run-cron', [ClientController::class, 'runCron']);


Route::get('completed-booking-request-stripe/{id}', [ClientController::class, 'completedBookingRequestStripe']);



/**
 * Stripe API's
 * **/
Route::post('create-stripe-connected-account', [ClientController::class, 'createStripeConnectedAccount']);
Route::post('transfer-fund-connected-account', [ClientController::class, 'transferFundConnectedAccount']);
Route::post('create-account-session', [ClientController::class, 'createAccountSession']);
Route::post('resend-verification-email', [AuthController::class, 'resendVerificationEmail']);