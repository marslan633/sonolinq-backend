<?php

namespace App\Http\Controllers\api\Client;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use DB;
use Carbon\Carbon;
use DateTime;
use Stripe\Stripe;
use Stripe\Charge;
use Exception;
use Illuminate\Support\Facades\Mail;
use App\Mail\DynamicMail;
use App\Models\{Client, Company, Booking, Preference, EligibleSonographer, Reservation, Service, BankInfo, Package, ServiceCategory, Registry, LevelSystem, Review, EmailTemplate, NotificationHistory, Transaction, ConnectedAccount};
use App\Traits\NotificationTrait;
use App\Traits\BusinessDaysTrait;

class DoctorController extends Controller
{
    use NotificationTrait, BusinessDaysTrait;

    /**
     * Check (Count) Eligibility Sonographer.
     */
    public function checkEligibility(Request $request) {
        try {
            $gender = $request->input('sonographer_gender');
            $level = $request->input('sonographer_level');
            $experience = $request->input('sonographer_experience');
            $register_no = $request->input('sonographer_registery');
            $language = $request->input('sonographer_language');
            $equipment = $request->input('sonographer_equipment');
            $type = $request->input('sonographer_type');
            
            $records = Company::with('client')
                ->whereHas('client', function ($query) {
                    $query->where('role', 'Sonographer')->where('status', 'active');
                })
                ->when($level, function ($query, $level) {
                    $query->where('level', $level);
                })
                ->when($experience, function ($query, $experience) {
                    $query->where('years_of_experience', '>=', $experience);
                })
                // ->when($register_no, function ($query) {
                //     $query->whereNotNull('register_no');
                // })
                ->when($language, function ($query) use ($language) {
                    $query->where(function ($subQuery) use ($language) {
                        foreach ($language as $lang) {
                            $subQuery->orWhere('languages_spoken', 'LIKE', '%' . $lang . '%');
                        }
                    });
                })
                ->when($equipment, function ($query) use ($equipment) {
                    $query->where(function ($subQuery) use ($equipment) {
                        foreach ($equipment as $equi) {
                            $subQuery->orWhere('type_of_equipment', 'LIKE', '%' . $equi . '%');
                        }
                    });
                })
                ->when($gender, function ($query, $gender) {
                    $query->whereHas('client', function ($subQuery) use ($gender) {
                        $subQuery->where('gender', $gender)
                            ->orWhereNull('gender')
                            ->orWhere('gender', '');
                    });
                })
                ->when($register_no == 'yes', function ($query) {
                    $query->whereHas('registries');
                })
                ->when($type, function ($query) use ($type) {
                    if ($type === 'Sonographer Only') {
                        $query->where('equipment_availability', 'No')
                            ->where('pacs_reading', 'No');
                    } elseif ($type === 'Sonographer w/Machine') {
                        $query->where('equipment_availability', 'Yes')
                            ->where('pacs_reading', 'No');
                    } elseif ($type === 'Sonographer w/Machine & PACS') {
                        $query->where('equipment_availability', 'Yes')
                            ->where('pacs_reading', 'Yes');
                    }
                })
                ->count();

            return sendResponse(true, 200, 'Sonographer Counts!', $records, 200);
        } catch (\Exception $ex) {
            return sendResponse(false, 500, 'Internal Server Error', $ex->getMessage(), 200);
        }
    }
    
    /**
     * Doctor Booking Appointment.
     */
    public function appointment(Request $request) { 
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
            
            // Creating preference first
            $preference = $request->all();
            
            if($request->input('sonographer_language')) {
                $arrayLanguage = $request->input('sonographer_language');
                $strLang = implode(',', $arrayLanguage);
                
                $preference['sonographer_language'] = $strLang;                                 
            }
            $preference = Preference::create($preference);
            
            $preferenceID = $preference->id;

            foreach ($request->reservations as $reservationData) {
                
                // Booking will create on the base of reservations
                $booking = $request->all();
                $client_id = Auth::guard('client-api')->user()->id;
                
                $booking['doctor_id'] = $client_id;
                if($request->amount) {
                    $booking['charge_amount'] = $charge->amount;
                }
                
                $booking['preference_id']= $preferenceID; 
                
                $booking = Booking::create($booking);

                // Generate booking_tracking_id
                $prefix = 'SNAPP';
                $randomNumber = mt_rand(1000, 9999);
                $booking->update(['booking_tracking_id' => $prefix . $randomNumber . $booking->id]);
                
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


                // Here we are assigning the booking to selected sonographers
                // Run eligibility check for sonographer
                $gender = $request->input('sonographer_gender');
                $level = $request->input('sonographer_level');
                $experience = $request->input('sonographer_experience');
                $register_no = $request->input('sonographer_registery');
                $language = $request->input('sonographer_language');
                $equipment = $request->input('sonographer_equipment');
                
                $records = Company::with('client')
                    ->whereHas('client', function ($query) {
                        $query->where('role', 'Sonographer')->where('status', 'active');
                    })
                    ->when($level, function ($query, $level) {
                        $query->where('level', $level);
                    })
                    ->when($experience, function ($query, $experience) {
                        $query->where('years_of_experience', '>=', $experience);
                    })
                    // ->when($register_no, function ($query) {
                    //     $query->whereNotNull('register_no');
                    // })
                    ->when($language, function ($query) use ($language) {
                        $query->where(function ($subQuery) use ($language) {
                            foreach ($language as $lang) {
                                $subQuery->orWhere('languages_spoken', 'LIKE', '%' . $lang . '%');
                            }
                        });
                    })
                    ->when($equipment, function ($query) use ($equipment) {
                        $query->where(function ($subQuery) use ($equipment) {
                            foreach ($equipment as $equi) {
                                $subQuery->orWhere('type_of_equipment', 'LIKE', '%' . $equi . '%');
                            }
                        });
                    })
                    ->when($gender, function ($query, $gender) {
                        $query->whereHas('client', function ($subQuery) use ($gender) {
                            $subQuery->where('gender', $gender)
                                ->orWhereNull('gender')
                                ->orWhere('gender', '');
                        });
                    })
                    ->when($register_no == 'yes', function ($query) {
                        $query->whereHas('registries');
                    })
                    ->get();

                $sonographers = $records;

                $emailTemplate = EmailTemplate::where('type', 'booking-request')->first();
                foreach($sonographers as $sonographer) {
                    $eligible = new EligibleSonographer();
                    $eligible->sonographer_id = $sonographer->client_id;
                    $eligible->booking_id = $booking->id;
                    $eligible->save();
                    
                    //Send Booking Request Email to Sonographers
                    if($emailTemplate) {
                        $details = [
                            'subject' => $emailTemplate->subject,
                            'body'=> $emailTemplate->body,
                            'type' => $emailTemplate->type,
                            'full_name' => $sonographer->client['full_name']
                        ];
                        Mail::to($sonographer->client['email'])->send(new DynamicMail($details)); 
                    }                         
                    // Mail::to($sonographer->client['email'])->send(new BookingRequestMail());

                    /* Send Booking Request Notification to Sonographers */
                    $tokens = [$sonographer->client['device_token']];
                    if($tokens) {
                        $title = "Appointment Request";
                        $body = "You have received appointment request from doctor";
                        $client_id = $sonographer->client['id'];
                        $module_id = $booking->id;
                        $module_name = "Booking Request";

                        $notification = new NotificationHistory();
                        $notification->title = $title;
                        $notification->body = $body;
                        $notification->module_id = $module_id;
                        $notification->module_name = $module_name;
                        $notification->client_id = $client_id;
                        $notification->save();
                        
                        $count = NotificationHistory::where('client_id', $client_id)->where('is_read', false)->count();
                        $this->sendNotification($tokens, $title, $body, $count);
                    }
                }
            }

            return sendResponse(true, 200, 'Appointment Created Successfully!', $preference, 200);
        } catch (\Exception $ex) {
            return sendResponse(false, 500, 'Internal Server Error', $ex->getMessage(), 200);
        }
    }

    /**
     * Get Doctor Booking.
     */
    public function getDoctorBookings(Request $request) {
        try {
            $bookings = Booking::where('doctor_id', Auth::user()->id)
                ->whereIn('status', explode(',', $request->status))
                ->with([
                    'reservation' => function ($query) {
                        $query->with(['serviceCategories', 'services.category']);
                    },
                    'sonographer',
                    'preferences',
                    'doctor',
                    'review'
                ])
                ->get();
            return sendResponse(true, 200, 'Booking List Fetched Successfully!', $bookings, 200);
        } catch (\Exception $ex) {
            return sendResponse(false, 500, 'Internal Server Error', $ex->getMessage(), 200);
        }
    }

    /**
     * Show Doctor Booking Public API.
     */
    public function showBooking($id) {
        try {
            // $bookings = Booking::where('id', $id)->with('service_category')->with('service')->with('doctor')->with('sonographer')->with('preferences')->get();

            $bookings = Booking::where('id', $id)
                ->with([
                    'reservation' => function ($query) {
                        $query->with(['serviceCategories' ,'services.category']);
                    },
                    'sonographer',
                    'preferences',
                    'doctor'
                ])
                ->first();
            return sendResponse(true, 200, 'Booking List Fetched Successfully!', $bookings, 200);
        } catch (\Exception $ex) {
            return sendResponse(false, 500, 'Internal Server Error', $ex->getMessage(), 200);
        }
    }

    /**
     * When Doctor Complete Booking Request.
     */
    public function completedBookingRequest($id) {
        try {
            $booking = Booking::with('reservation')->where('id', $id)->first();
            $sonographer = Client::where('id', $booking->sonographer_id)->with('package')->first();
            $bookingAmount = $booking->reservation['amount'];
            
            // this is for demo purpose we will remove it later when we work with packages and then update Balance of Sonographer
            // $calBalance = $sonographer->virtual_balance + $bookingAmount;
            // $sonographer->virtual_balance = $calBalance;
            // $sonographer->update();
                    
            // $booking->status = 'Completed';
            // $booking->save();

            // $booking['virtual_balance'] = $sonographer->virtual_balance;
            // return sendResponse(true, 200, 'Booking Request Completed Successfully!', $booking, 200);
            // this is for demo purpose we will remove it later when we work with packages and then update Balance of Sonographer


                
            
            $sonographerID = $booking->sonographer_id;

            // find sonographer package old implementation
            // $findPackage = Package::whereHas('clients', function ($query) use ($sonographerID) {
            //     $query->where('client_id', $sonographerID);
            // })->first();

            // find sonographer package
            $findPackage = $sonographer->package;

            if ($findPackage) {
                $packageType = $findPackage->type;

                if($packageType == 'percentage') {
                    $percentage = $findPackage->payment;
                    $amountToSubtract = ($percentage / 100) * $bookingAmount;
                } elseif ($packageType === 'fixed') {
                    $fixedAmount = $findPackage->payment; 
                    $amountToSubtract = $fixedAmount;
                }

                // Subtract the calculated amount
                $finalAmount = $bookingAmount - $amountToSubtract;     
                
                // find bank of sonographer
                $highPriorityBank = BankInfo::where('client_id', $sonographerID)
                    ->where('priority', 'high')
                    ->first();

                if ($highPriorityBank) {
                    // High priority bank found
                    $selectedBank = $highPriorityBank;
                } else {
                    // High priority not found, try medium priority
                    $mediumPriorityBank = BankInfo::where('client_id', $sonographerID)
                        ->where('priority', 'medium')
                        ->first();

                    if ($mediumPriorityBank) {
                        // Medium priority bank found
                        $selectedBank = $mediumPriorityBank;
                    } else {
                            // Medium priority not found, try low priority
                            $lowPriorityBank = BankInfo::where('client_id', $sonographerID)
                                ->where('priority', 'low')
                                ->first();

                            if ($lowPriorityBank) {
                                // Low priority bank found
                                $selectedBank = $lowPriorityBank;
                            } else {
                                // No banks found for the sonographer
                                return sendResponse(false, 200, 'No banks found for the sonographer', [], 200);
                            }
                    }
                }

                // update Balance of Sonographer
                $calBalance = $sonographer->virtual_balance + $finalAmount;
                $sonographer->virtual_balance = $calBalance;
                $sonographer->update();

                // Record transaction
                $transactionId = str_pad(rand(1, pow(10, 10) - 1), 10, '0', STR_PAD_LEFT);
            
                Transaction::create([
                    'client_id' => $sonographer->id,
                    'transaction_id' => $transactionId,
                    'amount' => $finalAmount,
                    'type' => 'deposit',
                    'created_at' => now(),
                ]);
                    
                $booking->status = 'Completed';
                $booking->save();

                $booking['virtual_balance'] = $sonographer->virtual_balance;
                return sendResponse(true, 200, 'Booking Request Completed Successfully!', $booking, 200);
            } else {
                return sendResponse(false, 200, 'Package not found for sonographer!', [], 200);
            }
        } catch (\Exception $ex) {
            return sendResponse(false, 500, 'Internal Server Error', $ex->getMessage(), 200);
        }
    }

    /**
     * When Doctor Cancel Booking Request.
     */
    public function doctorCancelBooking($id) {
        try {
            $booking = Booking::with('reservation')->where('id', $id)->first();
            $doctor = Client::where('id', $booking->doctor_id)->first();

            // Check if booking status is "Pending"
            if ($booking->status === 'Pending') {
                $booking->status = 'Cancelled';
                $booking->update();

                $calBalance = $doctor->virtual_balance + $booking->reservation['amount'];
                $doctor->virtual_balance =  $calBalance;
                $doctor->update();

                $booking['virtual_balance'] = $doctor->virtual_balance;
                
                EligibleSonographer::where('booking_id', $booking->id)->delete();
                return sendResponse(true, 200, 'Booking Request Cancelled Successfully!', $booking, 200);
            }

            $virtualBalance = $doctor->virtual_balance;
            $bookingAmount =  $booking->reservation['amount'];
            
            $shiftDate = new DateTime($booking->reservation['date']);
            // Calculate the number of business days between the current date and the shift date
            $currentDate = new DateTime();
            $businessDayCount = $this->countBusinessDays($currentDate, $shiftDate);

            // Determine the cancellation fee based on the business day count
            if($booking->status === 'Active') {
                if ($businessDayCount < 3) {
                    $cancellationFee = $bookingAmount - 250; // Late cancellation fee
                    $booking->cancellation_fee = $cancellationFee;
                    $booking->status = 'Cancelled';
                    $virtualBalance += $cancellationFee;
                    $booking->update();
                    
                    $doctor->virtual_balance = $virtualBalance;
                    $doctor->update();

                    $booking['virtual_balance'] = $doctor->virtual_balance;
 
                } else {
                    $booking->status = 'Cancelled';
                    $booking->update();

                    $calBalance = $doctor->virtual_balance + $booking->reservation['amount'];
                    $doctor->virtual_balance =  $calBalance;
                    $doctor->update();

                    $booking['virtual_balance'] = $doctor->virtual_balance;
                    
                    EligibleSonographer::where('booking_id', $booking->id)->delete();
                } 
                    // Send Booking Cancelation Email to Sonographer
                    $emailTemplate = EmailTemplate::where('type', 'booking-cancel')->first();

                    if($emailTemplate) {
                        $sonographerDetails = $booking->load('sonographer');
                        $details = [
                            'subject' => $emailTemplate->subject,
                            'body'=> $emailTemplate->body,
                            'type' => $emailTemplate->type,
                            'full_name' => $sonographerDetails->doctor['full_name']
                        ];

                        Mail::to($sonographerDetails->doctor['email'])->send(new DynamicMail($details)); 
                    }

                    /* Send Booking Cancelation Notification to Sonographer */
                    $tokens = [$booking->sonographer['device_token']];
                    if($tokens) {
                        $title = "Booking Cancelated!";
                        $body = "The doctor has cancelated the booking request!";
                        $sonographer_id = $booking->sonographer['id'];
                        $module_id = $booking->id;
                        $module_name = "Booking Cancelation";
                                
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
                return sendResponse(true, 200, 'Booking Request Cancelled Successfully!', $booking, 200);
            } else {
                return sendResponse(true, 200, 'Booking status is not active!', $booking, 200);
            }
        } catch (\Exception $ex) {
            return sendResponse(false, 500, 'Internal Server Error', $ex->getMessage(), 200);
        }
    }
}