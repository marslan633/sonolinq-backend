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


class SonographerController extends Controller
{
    use NotificationTrait, BusinessDaysTrait;
    
    public function getEligibleSonographers(Request $request) {
        try {
            $client_id = Auth::guard('client-api')->user()->id;
            // $bookingList = EligibleSonographer::with('booking.service', 'booking.service_category', 'booking.doctor', 'booking.sonographer')->where('sonographer_id', $client_id)->whereIn('status', explode(',', $request->status))->orderBy('id', 'desc')->get();
        
            $bookingList = EligibleSonographer::with([
                'booking.reservation' => function ($query) {
                    $query->with(['serviceCategories', 'services.category']);
                },
                'booking.doctor',
                'booking.sonographer',
                'booking.preferences',
                'booking.review'
            ])
            ->where('sonographer_id', $client_id)
            // ->whereIn('status', explode(',', $request->status))
            ->whereHas('booking', function ($query) use ($request) {
                $query->whereIn('status', explode(',', $request->status));
            })
            ->orderBy('id', 'desc')
            ->get();


            return sendResponse(true, 200, 'Booking List Fetched Successfully!', $bookingList, 200);
        } catch (\Exception $ex) {
            return sendResponse(false, 500, 'Internal Server Error', $ex->getMessage(), 200);
        }
    }
    
    /**
     * When Sonographer Accept Booking Request.
     */
    public function acceptBookingRequest($id) {
        try {
            $sonograhper = EligibleSonographer::find($id);            
            // $sonograhper->status = 'Active';
            // $sonograhper->save();
            

            $booking = Booking::find($sonograhper->booking_id);
            $booking->sonographer_id = $sonograhper->sonographer_id;
            $booking->status = 'Active';
            $booking->save();

            // Send Booking Accepted Email to Doctor
            $emailTemplate = EmailTemplate::where('type', 'booking-accept')->first();
            if($emailTemplate) {
                $doctorDetails = $booking->load('doctor');
                $details = [
                    'subject' => $emailTemplate->subject,
                    'body'=> $emailTemplate->body,
                    'type' => $emailTemplate->type,
                    'full_name' => $doctorDetails->doctor['full_name']
                ];

                Mail::to($doctorDetails->doctor['email'])->send(new DynamicMail($details)); 
            }

            /* Send Booking Accepted Notification to Sonographers */
            $tokens = [$booking->doctor['device_token']];
            if($tokens) {
                $title = "Appointment Accepted!";
                $body = "Your appointment request has been accepted from sonographer";
                $doctor_id = $booking->doctor['id'];
                $module_id = $booking->id;
                $module_name = "Booking Accepted";

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

            $client_id = Auth::guard('client-api')->user()->id;
            EligibleSonographer::where('sonographer_id', '!=', $client_id)->where('booking_id', $booking->id)->delete();
            
            return sendResponse(true, 200, 'Sonographer Accept Request Successfully!', $sonograhper, 200);
        } catch (\Exception $ex) {
            return sendResponse(false, 500, 'Internal Server Error', $ex->getMessage(), 200);
        }
    }

    /**
     * When Sonographer Reject Booking Request.
     */
    public function rejectBookingRequest($bookingID) {
        try {
            $checkBooking = EligibleSonographer::where('booking_id', $bookingID)->count();
            if($checkBooking > 0) {
                if($checkBooking === 1) {
                    $booking = Booking::find($bookingID);
                    $booking->status = 'Rejected';
                    $booking->save();
                }
                $client_id = Auth::guard('client-api')->user()->id;
                EligibleSonographer::where('sonographer_id', $client_id)->where('booking_id', $bookingID)->delete();

                return sendResponse(true, 200, 'Sonographer Reject Request Successfully!', [], 200);
            }
        } catch (\Exception $ex) {
            return sendResponse(false, 500, 'Internal Server Error', $ex->getMessage(), 200);
        }
    }

    /**
     * When Sonographer Cancel Booking Request.
     */    
    public function sonographerCancelBooking($id) {
        try {
            $booking = Booking::with('reservation')->where('id', $id)->first();
            $sonographer = Client::where('id', $booking->sonographer_id)->first();

            $countCancelBookings = Booking::where('sonographer_id', $booking->sonographer_id)
                                        ->where('status', 'Cancelled')
                                        ->count();

            if ($booking->status === 'Active') {
                $shiftDate = new DateTime($booking->reservation['date']);
                $currentDate = new DateTime();

                $businessDayCount = $this->countBusinessDays($currentDate, $shiftDate);
                
                // If cancellation is within the allowed limit of 3 individual shifts
                if ($businessDayCount >= 3 && $countCancelBookings < 3) {
                    $booking->status = 'Cancelled';
                    $booking->update();

                    $booking['virtual_balance'] = $sonographer->virtual_balance;

                    // Send Booking Cancelation Email to Doctor
                    $this->sendCancellationEmail($booking);

                    return sendResponse(true, 200, 'Booking Cancelled Successfully!', $booking, 200);
                } elseif ($businessDayCount >= 3 && $countCancelBookings >= 3 && $countCancelBookings < 6) {
                    // If cancellation exceeds the limit and account is not yet suspended
                    $booking->status = 'Cancelled';
                    $booking->update();
                    $sonographer->status = 'Suspended';
                    $sonographer->suspension_date = now()->toDateString();
                    $sonographer->update();

                    $booking['virtual_balance'] = $sonographer->virtual_balance;

                    // Send Booking Cancelation Email to Doctor
                    $this->sendCancellationEmail($booking);
                    
                    return sendResponse(false, 200, 'Account Suspended! You have exceeded the cancellation limit.', $booking, 200);
                } elseif ($countCancelBookings >= 6 || ($countCancelBookings >= 3 && $businessDayCount < 3)) {
                    // If cancellation exceeds the limit and account is already suspended or cancellation is within 72 hours notice
                    if ($sonographer->suspension_date !== null) {
                        $suspensionEndDate = Carbon::parse($sonographer->suspension_date)->addYear();
                        if ($sonographer->suspension_end_date !== null && $currentDate >= Carbon::parse($sonographer->suspension_end_date)) {
                            // If one year has passed since the last suspension, suspend for another year from the date of the 7th canceled shift
                            $suspensionEndDate = Carbon::parse($currentDate)->addYear();
                            $sonographer->suspension_date = now()->toDateString();
                            $sonographer->suspension_end_date = $suspensionEndDate->toDateString();
                            $sonographer->update();
                            
                            $booking['virtual_balance'] = $sonographer->virtual_balance;

                            // Send Booking Cancelation Email to Doctor
                            $this->sendCancellationEmail($booking);

                            return sendResponse(false, 200, 'Your account has been suspended again for a year!', $booking, 200);
                        } elseif ($sonographer->suspension_end_date === null) {
                            // If the account has never been suspended before
                            $suspensionEndDate = Carbon::parse($currentDate)->addYear();
                            $sonographer->status = 'Suspended';
                            $sonographer->suspension_date = now()->toDateString();
                            $sonographer->suspension_end_date = $suspensionEndDate->toDateString();
                            $sonographer->update();

                            $booking['virtual_balance'] = $sonographer->virtual_balance;

                            // Send Booking Cancelation Email to Doctor
                            $this->sendCancellationEmail($booking);

                            return sendResponse(false, 200, 'Your account has been suspended for a year!', $booking, 200);
                        } else {
                            return sendResponse(false, 200, 'Account is already suspended for a year!', $booking, 200);
                        }
                    } else {
                        // If the account has never been suspended before
                        $sonographer->status = 'Suspended';
                        $sonographer->suspension_date = now()->toDateString();
                        $sonographer->update();

                        $booking['virtual_balance'] = $sonographer->virtual_balance;

                        // Send Booking Cancelation Email to Doctor
                        $this->sendCancellationEmail($booking);
                    }
                    return sendResponse(false, 200, 'Account is suspended or cancellation not permitted within 72 hours notice.', $booking, 200);
                } else {
                    // If cancellation is within 72 hours notice
                    $sonographer->status = 'Suspended';
                    $sonographer->suspension_date = now()->toDateString();
                    $sonographer->update();

                    $booking['virtual_balance'] = $sonographer->virtual_balance;

                    // Send Booking Cancelation Email to Doctor
                    $this->sendCancellationEmail($booking);

                    return sendResponse(false, 200, 'Account is suspended immediately because cancellation not permitted within 72 hours notice.', $booking, 200);
                }
            } else {
                return sendResponse(false, 200, 'Booking is not active and cannot be cancelled.', $booking, 200);
            }
        } catch (\Exception $ex) {
            return sendResponse(false, 500, 'Internal Server Error', $ex->getMessage(), 200);
        }
        
    }

    /**
     * Extracted function to send cancellation email.
     */   
    private function sendCancellationEmail($booking) {
        $emailTemplate = EmailTemplate::where('type', 'booking-cancel')->first();

        if($emailTemplate) {
            $doctorDetails = $booking->load('doctor');
            $details = [
                'subject' => $emailTemplate->subject,
                'body'=> $emailTemplate->body,
                'type' => $emailTemplate->type,
                'full_name' => $doctorDetails->doctor['full_name']
            ];

            Mail::to($doctorDetails->doctor['email'])->send(new DynamicMail($details)); 
        }
        /* Send Booking Cancelation Notification to Doctor */
        $tokens = [$booking->doctor['device_token']];
        if($tokens) {
            $title = "Booking Cancelated!";
            $body = "The sonographer has been cancelated the booking request";
            $doctor_id = $booking->doctor['id'];
            $module_id = $booking->id;
            $module_name = "Booking Cancelation";

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
}