<?php

namespace App\Http\Controllers\api\Manager;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use DB;
use DateTime;
use App\Models\{Client, Company, Booking, Preference, EligibleSonographer, Reservation, Service, BankInfo, Package, ServiceCategory, Registry, LevelSystem, Review, EmailTemplate, NotificationHistory, Transaction, ConnectedAccount};


class ManagerController extends Controller
{
    /**
     * Stats API for admin.
     */
    public function adminStats() {
        try {
            $totalDoctors = Client::where('role', 'Doctor/Facility')->count();
            $totalSonographers = Client::where('role', 'Sonographer')->count();
            $totalUsers = Client::count();
            $totalServiceCategories = ServiceCategory::count(); 
            $totalServices = Service::count();
            
            $activeBooking = Booking::where('status', 'Active')->count();
            $deactiveBooking = Booking::where('status', 'Deactive')->count();
            $pendingBooking = Booking::where('status', 'Pending')->count();
            $deliveredBooking = Booking::where('status', 'Delivered')->count();
            $completedBooking = Booking::where('status', 'Completed')->count();
            $rejectedBooking = Booking::where('status', 'Rejected')->count();
            $totalEarning = Booking::where('status', 'Completed')->sum('charge_amount');

            $stats = [
                'totalDoctors' => $totalDoctors,
                'totalSonographers' => $totalSonographers,
                'totalUsers' => $totalUsers,
                'activeBooking' => $activeBooking,
                'deactiveBooking' => $deactiveBooking,
                'pendingBooking' => $pendingBooking,
                'deliveredBooking' => $deliveredBooking,
                'completedBooking' => $completedBooking,
                'rejectedBooking' => $rejectedBooking,
                'totalServiceCategories' => $totalServiceCategories,
                'totalServices' => $totalServices,
                'totalEarning' => $totalEarning
            ];

            return sendResponse(true, 200, 'Admin Stats!', $stats, 200);
        } catch (\Exception $ex) {
            return sendResponse(false, 500, 'Internal Server Error', $ex->getMessage(), 200);
        }
    }

    /**
     * Get list of Bookings.
     */
    public function bookingList() {
        try {
            // $bookings = Booking::with('service_category')->with('service')->with('doctor')->with('sonographer')->with('preferences')->get();
            $bookings = Booking::with([
                'reservation' => function ($query) {
                    $query->with(['serviceCategories', 'services']);
                },
                'doctor',
                'sonographer',
                'preferences',
                'review'
            ])
            ->get();
            return sendResponse(true, 200, 'Booking List Fetched Successfully!', $bookings, 200);
        } catch (\Exception $ex) {
            return sendResponse(false, 500, 'Internal Server Error', $ex->getMessage(), 200);
        }
    }

    /**
    * Chart API for admin
    */
    public function clientChart(Request $request)
    {
        $client = Auth::guard('user-api')->user();
        if ($client->role == 'Admin') {
            
            if ($request->parameter == "Today") {

                $today_date = Carbon::now()->toDateString();

                $date_groups = array(
                    array(
                        "start_time" => "01:00:00",
                        "end_time" => "03:00:00",
                    ),
                    array(
                        "start_time" => "03:01:00",
                        "end_time" => "05:00:00",
                    ),
                    array(
                        "start_time" => "05:01:00",
                        "end_time" => "07:00:00",
                    ),
                    array(
                        "start_time" => "07:01:00",
                        "end_time" => "09:00:00",
                    ),
                    array(
                        "start_time" => "09:01:00",
                        "end_time" => "11:59:00",
                    ),
                    array(
                        "start_time" => "12:00:00",
                        "end_time" => "14:00:00",
                    ),
                    array(
                        "start_time" => "14:01:00",
                        "end_time" => "16:00:00",
                    ),
                    array(
                        "start_time" => "16:01:00",
                        "end_time" => "18:00:00",
                    ),
                    array(
                        "start_time" => "18:01:00",
                        "end_time" => "20:00:00",
                    ),
                    array(
                        "start_time" => "20:01:00",
                        "end_time" => "22:00:00",
                    ),
                    array(
                        "start_time" => "22:01:00",
                        "end_time" => "23:59:00",
                    ),
                );

                $arr_data_sonographer = [];
                $arr_data_doctor = [];
                $arr_date = [];

                foreach ($date_groups as $date) {
                    $countSonographer = DB::table('clients')->whereDate('created_at', '=', $today_date)
                        ->whereTime('created_at', '>=', $date['start_time'])
                        ->whereTime('created_at', '<=', $date['end_time'])
                        ->where('role', 'Sonographer')->count();

                    $countDoctor = DB::table('clients')->whereDate('created_at', '=', $today_date)
                        ->whereTime('created_at', '>=', $date['start_time'])
                        ->whereTime('created_at', '<=', $date['end_time'])
                        ->where('role', 'Doctor/Facility')->count();

                    array_push($arr_data_sonographer, $countSonographer);
                    array_push($arr_data_doctor, $countDoctor);
                    array_push($arr_date, $date['start_time']);
                }

                $dataArray = array(
                    "data" => array(
                        "sonographer" => $arr_data_sonographer,
                        "doctor" => $arr_data_doctor,
                    ),
                    "date" => $arr_date
                );
                return sendResponse(true, 200, 'Charts result.', $dataArray, 200);
            }

            if ($request->parameter == "Weekly") {
                $start_of_week_sonographer = Carbon::now()->subDays(6);
                $start_of_week_doctor = Carbon::now()->subDays(6);

                $end_of_week = Carbon::today()->endOfWeek();

                $count_sonographer = [];
                $count_doctor = [];
                $date_array = [];

                for ($start_date = 1; $start_date < 8; $start_date++) {
                    // Sonographer count
                    $counterSonographer = Client::whereDate('created_at', '=', $start_of_week_sonographer)->where('role', 'Sonographer')->count();
                    $count_sonographer[] = $counterSonographer;

                    // Doctor count
                    $counterDoctor = Client::whereDate('created_at', '=', $start_of_week_doctor)->where('role', 'Doctor/Facility')->count();
                    $count_doctor[] = $counterDoctor;

                    // Common date array
                    $date_array[] = $start_of_week_sonographer->toDateString();

                    // Increment the start_of_week separately for each loop
                    $start_of_week_sonographer->addDay();
                    $start_of_week_doctor->addDay();
                }

                $dataArray = array(
                    "data" => array(
                        "sonographer" => $count_sonographer,
                        "doctor" => $count_doctor
                    ),
                    "date" => $date_array
                );

                return sendResponse(true, 200, 'Charts result.', $dataArray, 200);
            }

            if ($request->parameter == "Monthly") {
                $start_of_month = Carbon::now()->startOfMonth();
                $end_of_month = Carbon::now()->endOfMonth()->format('d');

                $count_sonographer = [];
                $count_doctor = [];
                $date_array = [];

                for ($start_date = 1; $start_date <= $end_of_month; $start_date++) {
                    $counterSonographer = Client::whereDate('created_at', '=', $start_of_month->toDateString())->where('role', 'Sonographer')->count();
                    $count_sonographer[] = $counterSonographer;

                    $counterDoctor = Client::whereDate('created_at', '=', $start_of_month->toDateString())->where('role', 'Doctor/Facility')->count();
                    $count_doctor[] = $counterDoctor;

                    $date_array[] = $start_of_month->toDateString();

                    $start_of_month->addDay();
                }

                $dataArray = array(
                    "data" => array(
                        "sonographer" => $count_sonographer,
                        "doctor" => $count_doctor
                    ),
                    "date" => $date_array
                );

                return sendResponse(true, 200, 'Charts result.', $dataArray, 200);
            }

            if ($request->parameter == "Yearly") {
                $start_of_year = Carbon::now()->startOfYear();
                $end_of_year = Carbon::now()->endOfYear();

                $count_sonographer = [];
                $count_doctor = [];
                $date_array = [];

                for ($start_of_year_in_month = 1; $start_of_year_in_month <= 12; $start_of_year_in_month++) {
                    $counterSonographer = Client::whereBetween('created_at', [
                        $start_of_year->startOfMonth()->toDateString(),
                        $start_of_year->endOfMonth()->toDateString()
                    ])->where('role', 'Sonographer')->count();

                    $counterDoctor = Client::whereBetween('created_at', [
                        $start_of_year->startOfMonth()->toDateString(),
                        $start_of_year->endOfMonth()->toDateString()
                    ])->where('role', 'Doctor/Facility')->count();

                    $count_sonographer[] = $counterSonographer;
                    $count_doctor[] = $counterDoctor;
                    $date_array[] = $start_of_year->startOfMonth()->toDateString();

                    $start_of_year->addMonth();
                }

                $dataArray = array(
                    "data" => array(
                        "sonographer" => $count_sonographer,
                        "doctor" => $count_doctor,
                    ),
                    "date" => $date_array
                );

                return sendResponse(true, 200, 'Charts result.', $dataArray, 200);
            }

            if ($request->parameter == "Custom" && isset($request->from) && isset($request->to)) {

                $from = Carbon::parse($request->from);
                $to = Carbon::parse($request->to);
                $diff_in_days = $to->diffInDays($from);

                $count_sonographer = [];
                $count_doctor = [];
                $date_array = [];

                for ($start_date = 0; $start_date <= $diff_in_days; $start_date++) {
                    $counterSonographer = Client::whereDate('created_at', '=', $from->toDateString())->where('role', 'Sonographer')->count();
                    $count_sonographer[] = $counterSonographer;

                    $counterDoctor = Client::whereDate('created_at', '=', $from->toDateString())->where('role', 'Doctor/Facility')->count();
                    $count_doctor[] = $counterDoctor;

                    $date_array[] = $from->toDateString();

                    $from->addDay();
                }

                $dataArray = array(
                    "data" => array(
                        "sonographer" => $count_sonographer,
                        "doctor" => $count_doctor,
                    ),
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
        } else {
            return response()->json([
                'status' => false,
                'message' => "You can't access this request!",
                'data' => [],
                'status_code' => 200
            ], 200);
        }
    }

    public function totalEarningChart(Request $request) 
    {
        $client = Auth::guard('user-api')->user();
        if ($client->role == 'Admin') {
            if ($request->parameter == "Today") {
                $today_date = Carbon::now()->toDateString();

                $date_groups = array(
                    array(
                        "start_time" => "01:00:00",
                        "end_time" => "03:00:00",
                    ),
                    array(
                        "start_time" => "03:01:00",
                        "end_time" => "05:00:00",
                    ),
                    array(
                        "start_time" => "05:01:00",
                        "end_time" => "07:00:00",
                    ),
                    array(
                        "start_time" => "07:01:00",
                        "end_time" => "09:00:00",
                    ),
                    array(
                        "start_time" => "09:01:00",
                        "end_time" => "11:59:00",
                    ),
                    array(
                        "start_time" => "12:00:00",
                        "end_time" => "14:00:00",
                    ),
                    array(
                        "start_time" => "14:01:00",
                        "end_time" => "16:00:00",
                    ),
                    array(
                        "start_time" => "16:01:00",
                        "end_time" => "18:00:00",
                    ),
                    array(
                        "start_time" => "18:01:00",
                        "end_time" => "20:00:00",
                    ),
                    array(
                        "start_time" => "20:01:00",
                        "end_time" => "22:00:00",
                    ),
                    array(
                        "start_time" => "22:01:00",
                        "end_time" => "23:59:00",
                    ),
                );

                $arr_data = [];
                $arr_date = [];
                foreach ($date_groups as $date) {
                    $count_users = DB::table('bookings')->whereDate('created_at', '=', $today_date)
                        ->whereTime('created_at', '>=', $date['start_time'])
                        ->whereTime('created_at', '<=', $date['end_time'])->sum('charge_amount');

                    array_push($arr_data, $count_users);
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

                for ($start_date = 1; $start_date < 8; $start_date++) {
                    $counterRecord =  Booking::whereDate('created_at', '=', $start_of_week)->sum('charge_amount');

                    $count_user[] = $counterRecord;
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


                for ($start_date = 1; $start_date <= $end_of_month; $start_date++) {
                    $counterRecord =  Booking::whereDate('created_at', '=', $start_of_month->toDateString())->sum('charge_amount');
                    $count_user[] = $counterRecord;
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

                $end_of_year_in_month = 12;
                for ($start_of_year_in_month = 1; $start_of_year_in_month <= $end_of_year_in_month; $start_of_year_in_month++) {
    
                    $counterRecord =  Booking::whereBetween('created_at', [$start_of_year->startOfMonth()->toDateString(), $start_of_year->endOfMonth()->toDateString()])->sum('charge_amount');
                    
                    $count_user[] = $counterRecord;
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

            if($request->parameter == "Custom" && isset($request->from) && isset($request->to)) {
                $from = Carbon::parse($request->from);
                $to = Carbon::parse($request->to);
                $diff_in_days = $to->diffInDays($from);

                for ($start_date = 0; $start_date <= $diff_in_days; $start_date++) {
                    $counterRecord =  Booking::whereDate('created_at', '=', $from->toDateString())->sum('charge_amount');
                    $count_user[] = $counterRecord;
                    $date_array[] = $from->toDateString();

                    $from->addDay();
                }

                $dataArray = array(
                    "data" => $count_user,
                    "date" => $date_array
                );

                return sendResponse(true, 200, 'Charts result.', $dataArray, 200);
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'Kindly pass three parameters (custom, from, to) in the request!',
                    'data' => [],
                    'status_code' => 200
                ], 200);
            }
        } else {
            return response()->json([
                'status' => false,
                'message' => "You can't access this request!",
                'data' => [],
                'status_code' => 200
            ], 200);
        }
    }

    public function bookingChart(Request $request) 
    {
        $client = Auth::guard('user-api')->user();
        if ($client->role == 'Admin') {
            if ($request->parameter == "Today") {
                $today_date = Carbon::now()->toDateString();

                $statuses = ['Pending', 'Active', 'Deactive', 'Delivered', 'Completed', 'Rejected'];
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

                $data_by_status = [];

                foreach ($date_groups as $date) {
                    $count_by_status = [];

                    foreach ($statuses as $status) {
                        $count_users = DB::table('bookings')
                            ->whereDate('created_at', '=', $today_date)
                            ->whereTime('created_at', '>=', $date['start_time'])
                            ->whereTime('created_at', '<=', $date['end_time'])
                            ->where('status', $status)->count();

                        $count_by_status[$status] = $count_users;
                    }

                    $data_by_status[] = $count_by_status;
                }

                $dataArray = array(
                    "data" => $data_by_status,
                    "date" => array_column($date_groups, 'start_time')
                );

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
                        $counterRecord = Booking::whereDate('created_at', '=', $start_of_week)->where('status', $status)->count();
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
                        $counterRecord = Booking::whereDate('created_at', '=', $start_of_month->toDateString())->where('status', $status)->count();
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
                        $counterRecord = Booking::whereBetween('created_at', [
                            $start_of_year->startOfMonth()->toDateString(),
                            $start_of_year->endOfMonth()->toDateString()
                        ])->where('status', $status)->count();

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

                for ($start_date = 0; $start_date <= $diff_in_days; $start_date++) {
                    $count_by_status = [];

                    foreach ($statuses as $status) {
                        $counterRecord = Booking::whereDate('created_at', '=', $from->toDateString())->where('status', $status)->count();
                        $count_by_status[$status] = $counterRecord;
                    }

                    $data_by_status[] = $count_by_status;
                    $date_array[] = $from->toDateString();

                    $from->addDay();
                }

                $dataArray = array(
                    "data" => $data_by_status,
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
        } else {
            return response()->json([
                'status' => false,
                'message' => "You can't access this request!",
                'data' => [],
                'status_code' => 200
            ], 200);
        }
    }

    public function getLevelSystem() {
        try {
            $items = LevelSystem::orderBy('id', 'asc')->get();
            return sendResponse(true, 200, 'Level System Fetched Successfully!', $items, 200);
        } catch (\Exception $ex) {
        return sendResponse(false, 500, 'Internal Server Error', $ex->getMessage(), 200);
        }
    }

    public function updateLevelSystem(Request $request, $id)
    {
        try {
            $item = LevelSystem::find($id);
            $item->update($request->all());
            
            return sendResponse(true, 200, 'Level System Updated Successfully!', $item, 200);
        } catch (\Exception $ex) {
            return sendResponse(false, 500, 'Internal Server Error', $ex->getMessage(), 200);
        }
    }
}