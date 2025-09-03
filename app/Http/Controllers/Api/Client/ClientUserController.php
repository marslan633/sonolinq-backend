<?php

namespace App\Http\Controllers\api\Client;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use DB;
use DateTime;
use App\Models\{Client, Company, Booking, Preference, EligibleSonographer, Reservation, Service, BankInfo, Package, ServiceCategory, Registry, LevelSystem, Review, EmailTemplate, NotificationHistory, Transaction, ConnectedAccount};

class ClientUserController extends Controller
{
    /**
    * Stats API for client
    */
    public function clientStats() {
        try {
            $client = Auth::guard('client-api')->user();
            $bookingType = ($client->role == "Sonographer") ? 'sonographer_id' : 'doctor_id';

            $activeBooking = Booking::where($bookingType, $client->id)->where('status', 'Active')->count();
            $deactiveBooking = Booking::where($bookingType, $client->id)->where('status', 'Deactive')->count();
            $pendingBooking = Booking::where($bookingType, $client->id)->where('status', 'Pending')->count();
            $deliveredBooking = Booking::where($bookingType, $client->id)->where('status', 'Delivered')->count();
            $completedBooking = Booking::where($bookingType, $client->id)->where('status', 'Completed')->count();
            $rejectedBooking = Booking::where($bookingType, $client->id)->where('status', 'Rejected')->count();
            $totalEarning = Booking::where($bookingType, $client->id)->where('status', 'Completed')->sum('charge_amount');
            $expectedEarning = Booking::where($bookingType, $client->id)->where('status', 'Active')->sum('charge_amount');

            $stats = [
                'activeBooking' => $activeBooking,
                'deactiveBooking' => $deactiveBooking,
                'pendingBooking' => $pendingBooking,
                'deliveredBooking' => $deliveredBooking,
                'completedBooking' => $completedBooking,
                'rejectedBooking' => $rejectedBooking,
                'totalEarning'=> $totalEarning,
                'expectedEarning' => $expectedEarning,
            ];

            return sendResponse(true, 200, 'Client Stats!', $stats, 200);
        } catch (\Exception $ex) {
            return sendResponse(false, 500, 'Internal Server Error', $ex->getMessage(), 200);
        }
    }

    /**
    * Chart API for client
    */
    public function clientEarningChart(Request $request)
    {
        $user =  Auth::guard('client-api')->user();

        if ($request->parameter == "Today") {
            $today_date = Carbon::now()->toDateString();

            $date_groups = array(
                array("start_time" => "01:00:00", "end_time" => "03:00:00"),
                array("start_time" => "03:01:00", "end_time" => "05:00:00"),
                array("start_time" => "05:01:00", "end_time" => "07:00:00"),
                array("start_time" => "07:01:00", "end_time" => "09:00:00"),
                array("start_time" => "09:01:00", "end_time" => "11:59:00"),
                array("start_time" => "12:00:00", "end_time" => "14:00:00"),
                array("start_time" => "14:01:00", "end_time" => "16:00:00"),
                array("start_time" => "16:01:00", "end_time" => "18:00:00"),
                array("start_time" => "18:01:00", "end_time" => "20:00:00"),
                array("start_time" => "20:01:00", "end_time" => "22:00:00"),
                array("start_time" => "22:01:00", "end_time" => "23:59:00"),
            );

            $arr_data = [];
            $arr_date = [];

            foreach ($date_groups as $date) {
                // Check the user's role
                if ($user->role == 'Sonographer') {
                    $count_users = Booking::whereDate('created_at', '=', $today_date)
                        ->whereTime('created_at', '>=', $date['start_time'])
                        ->whereTime('created_at', '<=', $date['end_time'])
                        ->where('sonographer_id', $user->id)
                        ->sum('charge_amount');
                } elseif ($user->role == 'Doctor/Facility') {
                    $count_users = Booking::whereDate('created_at', '=', $today_date)
                        ->whereTime('created_at', '>=', $date['start_time'])
                        ->whereTime('created_at', '<=', $date['end_time'])
                        ->where('doctor_id', $user->id)
                        ->sum('charge_amount');
                }

                array_push($arr_data, $count_users ?? 0);
                array_push($arr_date, $date['start_time']);
            }

            $dataArray = array(
                "data" => $arr_data,
                "date" => $arr_date
            );

            return sendResponse(true, 200, 'Charts result.', $dataArray, 200);
        }

        if ($request->parameter == "Weekly") {
                $start_of_week = Carbon::now()->subDays(6);
                $end_of_week = Carbon::today()->endOfWeek();

                $count_user = [];
                $date_array = [];

                for ($start_date = 1; $start_date < 8; $start_date++) {
                    // Check the user's role
                    if ($user->role == 'Sonographer') {
                        $counterRecord = Booking::whereDate('created_at', '=', $start_of_week)
                            ->where('sonographer_id', $user->id)
                            ->sum('charge_amount');
                    } elseif ($user->role == 'Doctor/Facility') {
                        $counterRecord = Booking::whereDate('created_at', '=', $start_of_week)
                            ->where('doctor_id', $user->id)
                            ->sum('charge_amount');
                    }

                    $count_user[] = $counterRecord ?? 0;
                    $date_array[] = $start_of_week->toDateString();

                    $start_of_week->addDay();
                }

                $dataArray = array(
                    "data" => $count_user,
                    "date" => $date_array
                );

                return sendResponse(true, 200, 'Charts result.', $dataArray, 200);
        }

        if ($request->parameter == "Monthly") {
            $start_of_month = Carbon::now()->startOfMonth();
            $end_of_month = Carbon::now()->endOfMonth()->format('d');

            $count_user = [];
            $date_array = [];

            for ($start_date = 1; $start_date <= $end_of_month; $start_date++) {
                // Check the user's role
                if ($user->role == 'Sonographer') {
                    $counterRecord = Booking::whereDate('created_at', '=', $start_of_month->toDateString())
                        ->where('sonographer_id', $user->id)
                        ->sum('charge_amount');
                } elseif ($user->role == 'Doctor/Facility') {
                    $counterRecord = Booking::whereDate('created_at', '=', $start_of_month->toDateString())
                        ->where('doctor_id', $user->id)
                        ->sum('charge_amount');
                }

                $count_user[] = $counterRecord ?? 0;
                $date_array[] = $start_of_month->toDateString();

                $start_of_month->addDay();
            }

            $dataArray = array(
                "data" => $count_user,
                "date" => $date_array
            );

            return sendResponse(true, 200, 'Charts result.', $dataArray, 200);
        }

        if ($request->parameter == "Yearly") {
            $start_of_year = Carbon::now()->startOfYear();
            $end_of_year = Carbon::now()->endOfYear();

            $count_user = [];
            $date_array = [];

            for ($start_of_year_in_month = 1; $start_of_year_in_month <= 12; $start_of_year_in_month++) {
                // Check the user's role
                if ($user->role == 'Sonographer') {
                    $counterRecord = Booking::whereBetween('created_at', [
                        $start_of_year->startOfMonth()->toDateString(),
                        $start_of_year->endOfMonth()->toDateString()
                    ])->where('sonographer_id', $user->id)->sum('charge_amount');
                } elseif ($user->role == 'Doctor/Facility') {
                    $counterRecord = Booking::whereBetween('created_at', [
                        $start_of_year->startOfMonth()->toDateString(),
                        $start_of_year->endOfMonth()->toDateString()
                    ])->where('doctor_id', $user->id)->sum('charge_amount');
                }

                $count_user[] = $counterRecord ?? 0;
                $date_array[] = $start_of_year->startOfMonth()->toDateString();

                $start_of_year->startOfMonth();
                $start_of_year->addMonth();
            }

            $dataArray = array(
                "data" => $count_user,
                "date" => $date_array
            );

            return sendResponse(true, 200, 'Charts result.', $dataArray, 200);
        }

        if ($request->parameter == "Custom" && isset($request->from) && isset($request->to)) {
            $from = Carbon::parse($request->from);
            $to = Carbon::parse($request->to);
            $diff_in_days = $to->diffInDays($from);

            $count_user = [];
            $date_array = [];

            for ($start_date = 0; $start_date <= $diff_in_days; $start_date++) {
                // Check the user's role
                if ($user->role == 'Sonographer') {
                    $counterRecord = Booking::whereDate('created_at', '=', $from->toDateString())
                        ->where('sonographer_id', $user->id)
                        ->sum('charge_amount');
                } elseif ($user->role == 'Doctor/Facility') {
                    $counterRecord = Booking::whereDate('created_at', '=', $from->toDateString())
                        ->where('doctor_id', $user->id)
                        ->sum('charge_amount');
                }

                $count_user[] = $counterRecord ?? 0;
                $date_array[] = $from->toDateString();

                $from->addDay();
            }

            $dataArray = array(
                "data" => $count_user,
                "date" => $date_array
            );

            return sendResponse(true, 200, 'Charts result.', $dataArray, 200);
        }
        else {
                return response()->json([
                    'status' => false,
                    'message' => 'Kindly pass three parameters (custom, from, to) in the request!',
                    'data' => [],
                    'status_code' => 200
                ], 200);
        }
    }

    public function clientBookingChart(Request $request) {
        $user = Auth::guard('client-api')->user();

        if ($request->parameter == "Today") {
            $today_date = Carbon::now()->toDateString();

            $statuses = ['Pending', 'Active', 'Deactive', 'Delivered', 'Completed', 'Rejected'];
            $date_groups = [
                ["start_time" => "01:00:00", "end_time" => "03:00:00"],
                ["start_time" => "03:01:00", "end_time" => "05:00:00"],
                ["start_time" => "05:01:00", "end_time" => "07:00:00"],
                ["start_time" => "07:01:00", "end_time" => "09:00:00"],
                ["start_time" => "09:01:00", "end_time" => "11:59:00"],
                ["start_time" => "12:00:00", "end_time" => "14:00:00"],
                ["start_time" => "14:01:00", "end_time" => "16:00:00"],
                ["start_time" => "16:01:00", "end_time" => "18:00:00"],
                ["start_time" => "18:01:00", "end_time" => "20:00:00"],
                ["start_time" => "20:01:00", "end_time" => "22:00:00"],
                ["start_time" => "22:01:00", "end_time" => "23:59:00"],
            ];

            $data_by_status = [];

            foreach ($date_groups as $date) {
                $count_by_status = [];

                foreach ($statuses as $status) {
                    $query = DB::table('bookings')
                        ->whereDate('created_at', '=', $today_date)
                        ->whereTime('created_at', '>=', $date['start_time'])
                        ->whereTime('created_at', '<=', $date['end_time'])
                        ->where('status', $status);

                    if ($user->role == 'Sonographer') {
                        $query->where('sonographer_id', $user->id);
                    } elseif ($user->role == 'Doctor/Facility') {
                        $query->where('doctor_id', $user->id);
                    }

                    $count_users = $query->count();
                    $count_by_status[$status] = $count_users;
                }

                $data_by_status[] = $count_by_status;
            }

            $dataArray = [
                "data" => $data_by_status,
                "date" => array_column($date_groups, 'start_time')
            ];

            return sendResponse(true, 200, 'Charts result.', $dataArray, 200);
        }

        if ($request->parameter == "Weekly") {
            $start_of_week = Carbon::now()->subDays(6);
            $end_of_week = Carbon::today()->endOfWeek();

            $statuses = ['Pending', 'Active', 'Deactive', 'Delivered', 'Completed', 'Rejected'];
            $data_by_status = [];

            for ($start_date = 1; $start_date < 8; $start_date++) {
                $count_by_status = [];

                foreach ($statuses as $status) {
                    // Check the user's role
                    if ($user->role == 'Sonographer') {
                        $counterRecord = Booking::whereDate('created_at', '=', $start_of_week)
                            ->where('sonographer_id', $user->id)
                            ->where('status', $status)
                            ->count();
                    } elseif ($user->role == 'Doctor/Facility') {
                        $counterRecord = Booking::whereDate('created_at', '=', $start_of_week)
                            ->where('doctor_id', $user->id)
                            ->where('status', $status)
                            ->count();
                    }

                    $count_by_status[$status] = $counterRecord;
                }

                $data_by_status[] = $count_by_status;
                $date_array[] = $start_of_week->toDateString();

                $start_of_week->addDay();
            }

            $dataArray = array(
                "data" => $data_by_status,
                "date" => $date_array
            );

            return sendResponse(true, 200, 'Charts result.', $dataArray, 200);
        }

        if ($request->parameter == "Monthly") {
            $start_of_month = Carbon::now()->startOfMonth();
            $end_of_month = Carbon::now()->endOfMonth()->format('d');

            $statuses = ['Pending', 'Active', 'Deactive', 'Delivered', 'Completed', 'Rejected'];
            $data_by_status = [];

            for ($start_date = 1; $start_date <= $end_of_month; $start_date++) {
                $count_by_status = [];

                foreach ($statuses as $status) {
                    // Check the user's role
                    if ($user->role == 'Sonographer') {
                        $counterRecord = Booking::whereDate('created_at', '=', $start_of_month->toDateString())
                            ->where('sonographer_id', $user->id)
                            ->where('status', $status)
                            ->count();
                    } elseif ($user->role == 'Doctor/Facility') {
                        $counterRecord = Booking::whereDate('created_at', '=', $start_of_month->toDateString())
                            ->where('doctor_id', $user->id)
                            ->where('status', $status)
                            ->count();
                    }

                    $count_by_status[$status] = $counterRecord;
                }

                $data_by_status[] = $count_by_status;
                $date_array[] = $start_of_month->toDateString();

                $start_of_month->addDay();
            }

            $dataArray = array(
                "data" => $data_by_status,
                "date" => $date_array
            );

            return sendResponse(true, 200, 'Charts result.', $dataArray, 200);
        }

        if ($request->parameter == "Yearly") {
            $start_of_year = Carbon::now()->startOfYear();
            $end_of_year = Carbon::now()->endOfYear();

            $statuses = ['Pending', 'Active', 'Deactive', 'Delivered', 'Completed', 'Rejected'];
            $data_by_status = [];

            for ($start_of_year_in_month = 1; $start_of_year_in_month <= 12; $start_of_year_in_month++) {
                $count_by_status = [];

                foreach ($statuses as $status) {
                    // Check the user's role
                    if ($user->role == 'Sonographer') {
                        $counterRecord = Booking::whereBetween('created_at', [
                            $start_of_year->startOfMonth()->toDateString(),
                            $start_of_year->endOfMonth()->toDateString()
                        ])->where('sonographer_id', $user->id)
                            ->where('status', $status)
                            ->count();
                    } elseif ($user->role == 'Doctor/Facility') {
                        $counterRecord = Booking::whereBetween('created_at', [
                            $start_of_year->startOfMonth()->toDateString(),
                            $start_of_year->endOfMonth()->toDateString()
                        ])->where('doctor_id', $user->id)
                            ->where('status', $status)
                            ->count();
                    }

                    $count_by_status[$status] = $counterRecord;
                }

                $data_by_status[] = $count_by_status;
                $date_array[] = $start_of_year->startOfMonth()->toDateString();

                $start_of_year->addMonth();
            }

            $dataArray = array(
                "data" => $data_by_status,
                "date" => $date_array
            );

            return sendResponse(true, 200, 'Charts result.', $dataArray, 200);
        }

        if ($request->parameter == "Custom" && isset($request->from) && isset($request->to)) {
            $from = Carbon::parse($request->from);
            $to = Carbon::parse($request->to);
            $diff_in_days = $to->diffInDays($from);

            $statuses = ['Pending', 'Active', 'Deactive', 'Delivered', 'Completed', 'Rejected'];
            $data_by_status = [];
            $date_array = [];

            for ($start_date = 0; $start_date <= $diff_in_days; $start_date++) {
                $count_by_status = [];

                foreach ($statuses as $status) {
                    $query = Booking::whereDate('created_at', '=', $from->toDateString())
                        ->where('status', $status);

                    if ($user->role == 'Sonographer') {
                        $query->where('sonographer_id', $user->id);
                    } elseif ($user->role == 'Doctor/Facility') {
                        $query->where('doctor_id', $user->id);
                    }

                    $counterRecord = $query->count();
                    $count_by_status[$status] = $counterRecord;
                }

                $data_by_status[] = $count_by_status;
                $date_array[] = $from->toDateString();

                $from->addDay();
            }

            $dataArray = [
                "data" => $data_by_status,
                "date" => $date_array
            ];

            return sendResponse(true, 200, 'Charts result.', $dataArray, 200);
        }
        else {
            return response()->json([
                    'status' => false,
                    'message' => 'Kindly pass three parameters (custom, from, to) in the request!',
                    'data' => [],
                    'status_code' => 200
            ], 200);
        }
    }

    public function validateToken() {
        try {
            if (Auth::guard('client-api')->check()) {
                return sendResponse(true, 200, 'Token is valid', [], 200);
            } else {
                return sendResponse(false, 200, 'Token is not valid, Please login again!', [], 200);
            }
        } catch (\Exception $ex) {
            return sendResponse(false, 500, 'Internal Server Error', $ex->getMessage(), 200);
        }
    }

    /**
    * This Api for both Doctor & Sonographer
    */
    public function updateBookingStatus(Request $request, $id) {
        try {
            $booking = Booking::find($id);
            $booking->status = $request->status;
            if($request->doctor_comments) {
                $booking->doctor_comments = $request->doctor_comments;
            }
            if($request->sonographer_comments) {
                $booking->sonographer_comments = $request->sonographer_comments;
            }

            $currentDateTime = now()->format('Y-m-d H:i:s');

            if ($request->status == 'Delivered') {
                $booking->delivery_date = $currentDateTime;
            }

            if ($request->status == 'Completed') {
                $booking->complete_date = $currentDateTime;
            }

            $booking->save();

            // Send Booking Delivered Email to Doctor
            if ($request->status == 'Delivered') {
                $emailTemplate = EmailTemplate::where('type', 'booking-deliver')->first();
                if($emailTemplate) {
                    $doctorDetails = $booking->load('doctor');
                    $details = [
                        'subject' => $emailTemplate->subject,
                        'body'=> $emailTemplate->body,
                        'type' => $emailTemplate->type,
                        'full_name' => $doctorDetails->doctor['full_name']
                    ];

                    //Mail::to($doctorDetails->doctor['email'])->send(new DynamicMail($details));
                }

                /* Send Booking Delivered Notification to Doctor */
                $tokens = [$booking->doctor['device_token']];
                if($tokens) {
                    $title = "Appointment Delivered!";
                    $body = "Your appointment request has been delivered from sonographer";
                    $doctor_id = $booking->doctor['id'];
                    $module_id = $booking->id;
                    $module_name = "Booking Delivered";

                    $notification = new NotificationHistory();
                    $notification->title = $title;
                    $notification->body = $body;
                    $notification->module_id = $module_id;
                    $notification->module_name = $module_name;
                    $notification->client_id = $doctor_id;
                    $notification->save();

                    $count = NotificationHistory::where('client_id', $doctor_id)->where('is_read', false)->count();
                    $this->sendNotification($tokens, $title, $body, $count);
                }
            }

            // Send Booking Completed Email to Sonographer
            if ($request->status == 'Completed') {

                $emailTemplate = EmailTemplate::where('type', 'booking-complete')->first();
                if($emailTemplate) {
                    $sonographerDetails = $booking->load('sonographer');
                    $details = [
                        'subject' => $emailTemplate->subject,
                        'body'=> $emailTemplate->body,
                        'type' => $emailTemplate->type,
                        'full_name' => $sonographerDetails->sonographer['full_name']
                    ];

                    //Mail::to($sonographerDetails->sonographer['email'])->send(new DynamicMail($details));
                }

                /* Send Booking Completed Notification to Sonographer */
                $tokens = [$booking->sonographer['device_token']];
                if($tokens) {
                    $title = "Appointment Completed!";
                    $body = "Your appointment request has been completed by the doctor.";
                    $sonographer_id = $booking->sonographer['id'];
                    $module_id = $booking->id;
                    $module_name = "Booking Completed";

                    $notification = new NotificationHistory();
                    $notification->title = $title;
                    $notification->body = $body;
                    $notification->module_id = $module_id;
                    $notification->module_name = $module_name;
                    $notification->client_id = $sonographer_id;
                    $notification->save();

                    $count = NotificationHistory::where('client_id', $sonographer_id)->where('is_read', false)->count();
                    $this->sendNotification($tokens, $title, $body, $count);
                }
            }

            $bookingObj = Booking::where('id', $booking->id)
                ->with([
                    'reservation' => function ($query) {
                        $query->with(['serviceCategories' ,'services.category']);
                    },
                    'sonographer',
                    'preferences',
                    'doctor'
                ])
                ->first();

            return sendResponse(true, 200, 'Booking Status Update Successfully!', $bookingObj, 200);
        } catch (\Exception $ex) {
            return sendResponse(false, 500, 'Internal Server Error', $ex->getMessage(), 200);
        }
    }

    /**
    * When Sonographer & Doctor Direct Book Appointment.
    */
    public function directBooking(Request $request) {
        try {
            if($request->input('token')) {
                Stripe::setApiKey(config('services.stripe.secret'));
                try {
                    $charge = Charge::create([
                        'amount' => $request->input('amount') * 100, // Convert amount to cents
                        'currency' => 'usd',
                        'source' => $request->input('token'), // Token received from client
                        'description' => 'SonoLinq Service Payment',
                    ]);
                } catch (\Exception $e) {
                    // Handle payment error here
                    return response()->json(['error' => $e->getMessage()], 500);
                }
            }

            foreach ($request->reservations as $reservationData) {

                // Booking will create on the base of reservations
                $client_id = Auth::guard('client-api')->user()->id;

                $bookingData = $request->all();

                $defaultStatus = 'Pending';

                if ($request->type == 'Doctor/Facility') {
                    $bookingData['doctor_id'] = $client_id;
                    $bookingData['sonographer_id'] = $request->sonographer_id;
                } elseif ($request->type == 'Sonographer') {
                    $bookingData['doctor_id'] = $request->doctor_id;
                    $bookingData['sonographer_id'] = $client_id;
                    $defaultStatus = 'Active';
                }

                if ($request->amount) {
                    $bookingData['charge_amount'] = $charge->amount;
                }

                $booking = Booking::create($bookingData);

                // Generate booking_tracking_id
                $prefix = 'SNAPP';
                $randomNumber = mt_rand(1000, 9999);
                $booking->update(['booking_tracking_id' => $prefix . $randomNumber . $booking->id]);

                if ($request->type == 'Sonographer') {
                    $booking->status = $defaultStatus;
                    $booking->save();
                }

                // Creating reservation
                $reservation = Reservation::create([
                    'type' => $reservationData['type'],
                    'date' => $reservationData['date'],
                    'time' => $reservationData['time'],
                    'amount' => $reservationData['amount'],
                    'booking_id' => $booking->id,
                ]);

                $reservation->serviceCategories()->attach($reservationData['service_category_id']);
                $reservation->services()->attach($reservationData['service_id']);
            }

            return sendResponse(true, 200, 'Appointment Book Successfully!', $booking, 200);
        } catch (\Exception $ex) {
            return sendResponse(false, 500, 'Internal Server Error', $ex->getMessage(), 200);
        }
    }

    /**
    * Get & Update Client Details API.
    */
    public function updateClient(Request $request)
    {
        try {
            /*Creating Client*/
            $id =  Auth::guard('client-api')->user()->id;
            $client = Client::find($id);
            if ($request->hasFile('non_solicitation_agreement')) {
                !is_null($client->non_solicitation_agreement) && Storage::disk('public')->delete($client->non_solicitation_agreement);
            }
            $client->update($request->all());
            if ($request->hasFile('non_solicitation_agreement')) {
                $client['non_solicitation_agreement'] = $request->file('non_solicitation_agreement')->store('companyImages', 'public');
                $client->save();
            }
            /*Creating Company*/
            $company = $request->all();
            if ($request->hasFile('personal_director_id')) {
                $company['personal_director_id'] = $request->file('personal_director_id')->store('companyImages', 'public');
                !is_null($client->company->personal_director_id) && Storage::disk('public')->delete($client->company->personal_director_id);
            }
            if ($request->hasFile('prove_of_address')) {
                $company['prove_of_address'] = $request->file('prove_of_address')->store('companyImages', 'public');
                !is_null($client->company->prove_of_address) && Storage::disk('public')->delete($client->company->prove_of_address);
            }
            if (isset($request->company_name)) {
                $client->company->update($company);
            }

            // if (isset($request->type_of_sonograms)) {
            //     $company = $client->company;
            //     $company->type_of_sonograms()->detach();
            //     $company->type_of_sonograms()->attach($request->type_of_sonograms);
            // }
            $clientId = $client->id;
            $companyId = $client->company->id;
            $totalRegNo = $request->total_reg_no;

            if(isset($request->total_reg_no)) {
                for ($i = 1; $i <= $totalRegNo; $i++) {

                    $registry = new Registry();
                    $registry->client_id = $clientId;
                    $registry->company_id = $companyId;

                    $registry->register_no = $request->{"register_no_$i"};
                    if ($request->hasFile("reg_no_letter_$i")) {
                        $registry['reg_no_letter'] = $request->file("reg_no_letter_$i")->store('companyImages', 'public');
                    }
                    $registry->save();
                }
            }

            if(isset($request->update_reg_arr)) {
                $updateRegArr = json_decode($request->update_reg_arr);

                foreach($updateRegArr as $regId){
                    $findReg = Registry::find($regId);
                    if($findReg) {
                        $regtryID = $findReg->id;
                        if($request->{"update_register_no_$regtryID"}) {
                            $findReg->register_no = $request->{"update_register_no_$regtryID"};
                        }

                        $regNoLetterKey = "update_reg_no_letter_$regtryID";
                        if ($request->hasFile($regNoLetterKey)) {
                            $findReg->reg_no_letter = $request->file($regNoLetterKey)->store('companyImages', 'public');
                            if (!is_null($findReg->reg_no_letter)) {
                                Storage::disk('public')->delete($findReg->reg_no_letter);
                            }
                        }
                        $findReg->update();
                    }
                }
            }

            if (isset($request->type_of_sonograms)) {
                $company = $client->company;
                $company->type_of_sonograms()->detach();

                $serviceIds = json_decode($request->type_of_sonograms, true);

                foreach ($serviceIds as $serviceId) {
                    $company->type_of_sonograms()->attach($serviceId);
                }
            }

            /*Creating Address*/
            if (isset($request->personal_address)) {
                $client->addresses()->update((array)json_decode($request->personal_address));
            }


            // if (isset($request->parcel_return_address)) {
            //     $client->addresses()->create((array)json_decode($request->parcel_return_address));
            // }
            $client = Client::with('company.type_of_sonograms', 'company.registries', 'package')->find($id);

            return sendResponse(true, 200, 'Client Updated Successfully!', $client, 200);
        } catch (\Exception $ex) {
            return sendResponse(false, 500, 'Internal Server Error', $ex->getMessage(), 200);
        }
    }

    public function getClient() {
        try {
            $id =  Auth::guard('client-api')->user()->id;
            $client = Client::with('company.type_of_sonograms', 'company.registries', 'addresses', 'package')->find($id);
            return sendResponse(true, 200, 'Client Fetched Successfully!', $client, 200);
        } catch (\Exception $ex) {
            return sendResponse(false, 500, 'Internal Server Error', $ex->getMessage(), 200);
        }
    }

    /***
     * get client profile
     *
     * **/
    public function client_statements()
    {
        try {
            $id =  Auth::guard('client-api')->user()->id;
            $client = Client::with('topups')->find($id)->topups;
            return sendResponse(true, 200, 'Client Topups Fetched Successfully!', $client, 200);
        } catch (\Exception $ex) {
            return sendResponse(false, 500, 'Internal Server Error', $ex->getMessage(), 200);
        }
    }

    /***
     * get client profile
     *
     * **/
    public function get_configurations()
    {
        try {
            $id =  Auth::guard('client-api')->user()->id;
            $client = Client::with('configuration')->find($id)->configuration;
            return sendResponse(true, 200, 'Client Configurations Fetched Successfully!', $client, 200);
        } catch (\Exception $ex) {
            return sendResponse(false, 500, 'Internal Server Error', $ex->getMessage(), 200);
        }
    }

    /***
     * get client profile
     *
     * **/
    public function update_configurations(Request $request)
    {
        try {
            $id =  Auth::guard('client-api')->user()->id;
            $checkConfiguration = Configuration::where('client_id', $id)->first();
            $data = $request->all();
            $data['client_id'] = $id;
            if (is_null($checkConfiguration)) {
                $checkConfiguration = Configuration::create($data);
            } else {
                $checkConfiguration->update($data);
            }
            return sendResponse(true, 200, 'Client Configuration Fetched Successfully!', $checkConfiguration, 200);
        } catch (\Exception $ex) {
            return sendResponse(false, 500, 'Internal Server Error', $ex->getMessage(), 200);
        }
    }
}
