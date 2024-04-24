<?php

namespace App\Http\Controllers\Api\Manager;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Manager\UpdateClientRequest;
use App\Models\{Client, Company, Booking, Preference, EligibleSonographer, Reservation, Service, BankInfo, Package, ServiceCategory, Registry, LevelSystem, Review, EmailTemplate, NotificationHistory};
use App\Models\Configuration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Stripe\Stripe;
use Stripe\Charge;
use DB;
use Illuminate\Support\Facades\Mail;
use App\Mail\BookingRequestMail;
use App\Mail\DynamicMail;
use Stripe\Transfer;
use Stripe\Payout;
use Stripe\Customer;
use Stripe\Token;
use Stripe\BankAccount;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use DateTime;
use App\Traits\NotificationTrait;
use Stripe\Account;
use Stripe\Exception\InvalidRequestException;

class ClientController extends Controller
{
    use NotificationTrait;
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $staff = Client::with('company.type_of_services', 'company.registries', 'addresses', 'package')
                ->whereIn('status', explode(',', $request->status))
                ->orderBy('id', 'desc')
                ->get();

            return sendResponse(true, 200, 'Clients Fetched Successfully!', $staff, 200);
        } catch (\Exception $ex) {
            return sendResponse(false, 500, 'Internal Server Error', $ex->getMessage(), 200);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(String $id)
    {
        
        try {
            $client = Client::with('company.type_of_services', 'company.registries', 'addresses', 'package')->find($id);
            return sendResponse(true, 200, 'Client Fetched Successfully!', $client, 200);
        } catch (\Exception $ex) {
            return sendResponse(false, 500, 'Internal Server Error', $ex->getMessage(), 200);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateClientRequest $request, String $id)
    {
        try {
            /*Creating Client*/
            $client = Client::find($id);
            $previousStatus = $client->status;
            
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

            if ($request->status === 'Active' && $previousStatus === 'Pending' && $client->role == 'Sonographer') {
                // Send Welcome Email
                $emailTemplate = EmailTemplate::where('type', 'welcome')->first();
                
                if($emailTemplate) {
                    $details = [
                        'subject' => $emailTemplate->subject,
                        'body'=> $emailTemplate->body,
                        'type' => $emailTemplate->type,
                        'full_name' => $client->full_name,
                        'url' => $request->url,
                    ];
                    Mail::to($client->email)->send(new DynamicMail($details));
                }
            }

            // if (isset($request->type_of_services)) { 
            //     $company = $client->company;
            //     $company->type_of_services()->detach();
            //     $company->type_of_services()->attach($request->type_of_services);
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

            if (isset($request->type_of_services)) {
                $company = $client->company;
                $company->type_of_services()->detach();

                $serviceIds = json_decode($request->type_of_services, true);

                foreach ($serviceIds as $serviceId) {
                    $company->type_of_services()->attach($serviceId);
                }
            }
            
            /*Creating Address*/
            if (isset($request->personal_address)) {
                $client->addresses()->update((array)json_decode($request->personal_address));
            }

            
            // if (isset($request->parcel_return_address)) {
            //     $client->addresses()->create((array)json_decode($request->parcel_return_address));
            // }
            $client = Client::with('company.type_of_services', 'company.registries', 'package')->find($id);

            if ($client && in_array($client->status, ['Deactive', 'Rejected', 'Suspended'])) {
                // Send email to the client
                $emailTemplate = EmailTemplate::where('type', 'inactive-client-email')->first();
                if($emailTemplate) {
                    $details = [
                        'subject' => $emailTemplate->subject,
                        'body'=> $emailTemplate->body,
                        'type' => $emailTemplate->type,
                        'full_name' => $client->full_name,
                        'reason' => $request->reason,
                    ];
                    Mail::to($client->email)->send(new DynamicMail($details));
                }
            }

            return sendResponse(true, 200, 'Client Updated Successfully!', $client, 200);
        } catch (\Exception $ex) {
            return sendResponse(false, 500, 'Internal Server Error', $ex->getMessage(), 200);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(String $id)
    {
        try {
            Client::destroy($id);
            return sendResponse(true, 200, 'Client Deleted Successfully!', [], 200);
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


    public function getClient() { 
        try {
            $id =  Auth::guard('client-api')->user()->id;
            $client = Client::with('company.type_of_services', 'company.registries', 'addresses', 'package')->find($id);
            return sendResponse(true, 200, 'Client Fetched Successfully!', $client, 200);
        } catch (\Exception $ex) {
            return sendResponse(false, 500, 'Internal Server Error', $ex->getMessage(), 200);
        }
    }


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

            // if (isset($request->type_of_services)) { 
            //     $company = $client->company;
            //     $company->type_of_services()->detach();
            //     $company->type_of_services()->attach($request->type_of_services);
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

            if (isset($request->type_of_services)) {
                $company = $client->company;
                $company->type_of_services()->detach();

                $serviceIds = json_decode($request->type_of_services, true);

                foreach ($serviceIds as $serviceId) {
                    $company->type_of_services()->attach($serviceId);
                }
            }
            
            /*Creating Address*/
            if (isset($request->personal_address)) {
                $client->addresses()->update((array)json_decode($request->personal_address));
            }

            
            // if (isset($request->parcel_return_address)) {
            //     $client->addresses()->create((array)json_decode($request->parcel_return_address));
            // }
            $client = Client::with('company.type_of_services', 'company.registries', 'package')->find($id);

            return sendResponse(true, 200, 'Client Updated Successfully!', $client, 200);
        } catch (\Exception $ex) {
            return sendResponse(false, 500, 'Internal Server Error', $ex->getMessage(), 200);
        }
    }

    public function checkEligibility(Request $request) {
        try {
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
                ->count();

            return sendResponse(true, 200, 'Sonographer Counts!', $records, 200);
        } catch (\Exception $ex) {
            return sendResponse(false, 500, 'Internal Server Error', $ex->getMessage(), 200);
        }
    }

    public function appointment(Request $request) { 
        try {
            if($request->input('token')) {
                Stripe::setApiKey("sk_test_51Nu9mBDJ9oRgyjebvyDL1NNHOBjkrZr5iViQNeKjSPWcAG801TmBkQo2mKvcsYDnviyRDFlCU0vF5I85jUPpg01f00p1BpqPeH");
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

    // Api for admin
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

    // Api for doctor
    public function getDoctorBookings(Request $request) {
        try {
            // $bookings = Booking::where('doctor_id', Auth::user()->id)->whereIn('status', explode(',', $request->status))->with('service_category')->with('service')->with('doctor')->with('sonographer')->with('preferences')->get();
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

    // Api for doctor
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

    // this api for doctor/sonographer
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

                    Mail::to($doctorDetails->doctor['email'])->send(new DynamicMail($details)); 
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

                    Mail::to($sonographerDetails->sonographer['email'])->send(new DynamicMail($details)); 
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

    // When Doctor Complete Booking
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

    // When Doctor Cancel Booking
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

    private function countBusinessDays($startDate, $endDate)
    {
        $count = 0;
        $currentDate = clone $startDate;

        while ($currentDate <= $endDate) {
            if ($currentDate->format("N") < 6) { // Monday to Friday are business days (N: ISO-8601 numeric representation of the day of the week)
                $count++;
            }
            $currentDate->modify('+1 day');
        }

        return $count;
    }

    // When Sonographer Cancel Booking
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

    // Extracted function to send cancellation email
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

    // My changes in process working

    /**
     * Stripe Connected Account API's.
     */
    public function createStripeConnectedAccount(Request $request) {
        try {
            $frontFilePath = $request->file('document_front')->getRealPath();
            $backFilePath = $request->file('document_back')->getRealPath();
            $frontFileContents = Storage::get($frontFilePath);
            $backFileContents = Storage::get($backFilePath);

            // Set your Stripe secret key
            Stripe::setApiKey("sk_test_51Nu9mBDJ9oRgyjebvyDL1NNHOBjkrZr5iViQNeKjSPWcAG801TmBkQo2mKvcsYDnviyRDFlCU0vF5I85jUPpg01f00p1BpqPeH");
            // Create connected account
            $account = \Stripe\Account::create([
                'type' => 'custom',
                'country' => $request->country, // Required: country code of the account
                'business_type' => $request->business_type, // Required: type of business (individual, company)
                'capabilities' => [
                    'card_payments' => ['requested' => true],
                    'transfers' => ['requested' => true],
                ],
                'business_profile' => [
                    "mcc" => $request->industry_mcc,
                    'name' => $request->business_name, // Required: business name
                    'url' => $request->business_url, // Optional: business website URL
                    'support_email' => $request->support_email, // Optional: support email address
                    'support_phone' => $request->support_phone, // Optional: support phone number
                ],
                'individual' => [
                    'first_name' => $request->first_name, // Required: first name of the individual
                    'last_name' => $request->last_name, // Required: last name of the individual
                    'email' => $request->email, // Required: email address of the individual
                    'phone' => $request->phone_number, // Phone number
                    'dob' => [
                        'day' => $request->dob_day, // Required: day of birth
                        'month' => $request->dob_month, // Required: month of birth
                        'year' => $request->dob_year, // Required: year of birth
                    ],
                    'ssn_last_4' => $request->ssn_last_4, // Last 4 digits of SSN
                    'address' => [
                        'line1' => $request->address_line1, // Required: address line 1
                        'city' => $request->address_city, // Required: city
                        'postal_code' => $request->address_postal_code, // Required: postal code
                        'state' => $request->address_state, // Required: state
                        'country' => $request->address_country, // Required: country code
                    ],
                    'verification' => [
                        'document' => [ // Required: document verification
                            'front' => base64_encode($frontFileContents), // Required: front side of the document
                            'back' => base64_encode($backFileContents), // Optional: back side of the document
                        ],
                    ],
                ],
                'tos_acceptance' => [
                    'date' => time(), // Required: current timestamp
                    'ip' => $request->ip(), // Required: IP address of the account holder
                ],
            ]);

            // Add bank account details
            $bankAccount = \Stripe\Account::createExternalAccount(
                $account->id,
                [
                    'external_account' => [
                        'object' => 'bank_account', // Specify the type of external account
                        'country' => $request->bank_country,  // Replace with the connected account's country code
                        'currency' => $request->currency,
                        'account_holder_name' => $request->account_holder_name,  // Replace with the account holder's name
                        'account_holder_type' => $request->account_holder_type,  // Replace with 'individual' or 'company'
                        'routing_number' => $request->routing_number,  // Replace with the connected account's bank routing number
                        'account_number' => $request->account_number,  // Replace with the connected account's bank account number
                    ],
                ]
            );

            // Return the created connected account
            return $account;
        } catch (InvalidRequestException $e) {
            // Handle exceptions
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function transferFundConnectedAccount(Request $request) {
        try {
            // Get recipient's Stripe account ID
            $recipientAccountId = $request->input('recipient_account_id');

            // Retrieve account information
            $account = \Stripe\Account::retrieve($recipientAccountId);

            // Check verification status
            if ($account->charges_enabled && $account->details_submitted) {
                // Account is verified, proceed with transfer
                $transfer = Transfer::create([
                    'amount' => 1000, // Amount in cents
                    'currency' => 'usd',
                    'destination' => $recipientAccountId,
                ]);

                // Handle response
                if ($transfer) {
                    // Transfer successful
                    return response()->json(['message' => 'Transfer successful'], 200);
                } else {
                    // Transfer failed
                    return response()->json(['message' => 'Transfer failed'], 500);
                }
            } else {
                // Account is not verified
                return response()->json(['message' => 'Recipient account is not verified'], 400);
            }
        } catch (InvalidRequestException $e) {
            // Handle exceptions
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    
    public function completedBookingRequestStripe($id) {
// Set your Stripe secret key
Stripe::setApiKey("sk_test_51Nu9mBDJ9oRgyjebvyDL1NNHOBjkrZr5iViQNeKjSPWcAG801TmBkQo2mKvcsYDnviyRDFlCU0vF5I85jUPpg01f00p1BpqPeH");

    $account = \Stripe\Account::retrieve('acct_1ObeZlD0H0LhSNKm');

    // Update the account to include tos_acceptance
    $account->tos_acceptance = [
        'date' => time(),
        'ip' => $_SERVER['REMOTE_ADDR'], // Assumes you're not using a proxy
    ];
    $account->save();


    $transfer = \Stripe\Transfer::create([
        'amount' => 1000,  // amount in cents
        'currency' => 'usd',
        'destination' => $account->id,  // customer's ID
    ]);

    return $transfer;


    

    
try {
$account = \Stripe\Account::create([
        'type' => 'custom',
        'country' => 'US',  // Replace with the connected account's country code
        'email' => 'connected_account@example.com',  // Email of the connected account
        'capabilities' => [
            'card_payments' => ['requested' => true],
            'transfers' => ['requested' => true],
        ],
        'tos_acceptance' => [
            'date' => time(),
            'ip' => $_SERVER['REMOTE_ADDR'],
        ],
    ]);
    
    $connected_account_id = $account->id;

    // Create a bank account token using the connected account's bank account details
    $bank_account_token = \Stripe\Token::create([
        'bank_account' => [
            'country' => 'US',  // Replace with the connected account's country code
            'currency' => 'usd',
            'account_holder_name' => 'John Doe',  // Replace with the account holder's name
            'account_holder_type' => 'individual',  // Replace with 'individual' or 'company'
            'routing_number' => '110000000',  // Replace with the connected account's bank routing number
            'account_number' => '000123456789',  // Replace with the connected account's bank account number
        ],
    ]);


    // $account = \Stripe\Account::retrieve($connected_account_id);

    // // Update the account to include tos_acceptance
    // $account->tos_acceptance = [
    //     'date' => time(),
    //     'ip' => $_SERVER['REMOTE_ADDR'], // Assumes you're not using a proxy
    // ];
    // $account->save();



    // $external_account = $account->external_accounts->create([
    //     'external_account' => $bank_account_token->id,
    // ]);

    

    // Attach the bank account token to the connected account
    $external_account = \Stripe\Account::createExternalAccount(
        $connected_account_id,
        [
            'external_account' => $bank_account_token->id,
        ]
    );


    
    // Access the bank account ID
    $bankAccountId = $external_account->id;


    $transfer = \Stripe\Transfer::create([
        'amount' => 1000,  // amount in cents
        'currency' => 'usd',
        'destination' => $bankAccountId,  // customer's ID
    ]);

    return $transfer;
   















    $customer = \Stripe\Customer::create([
        'email' => 'customer@example.com', 
        'description' => 'Customer description',
    ]);
   $customer_id = $customer->id;
   
    // Create a bank account token using the customer's bank account details
    $bank_account_token = \Stripe\Token::create([
        'bank_account' => [
            'country' => 'US', 
            'currency' => 'usd',
            'account_holder_name' => 'John Doe', 
            'account_holder_type' => 'individual', 
            'routing_number' => '110000000', 
            'account_number' => '000123456789',
        ],
    ]);

    // Attach the bank account to the customer
    $customer = \Stripe\Customer::update($customer_id, [
        'source' => $bank_account_token->id,
    ]);

    $transfer = \Stripe\Transfer::create([
        'amount' => 1000,  // amount in cents
        'currency' => 'usd',
        'destination' => $customer_id,  // customer's ID
    ]);

    return $transfer;

    
} catch (\Stripe\Exception\ApiErrorException $e) {
    // Handle API errors
    return $e->getMessage();
}


// Test bank details
$testBankDetails = [
    'country' => 'US',
    'currency' => 'usd',
    'account_number' => '000123456789', // Use a valid test account number
    'routing_number' => '110000000', // Use a valid test routing number (9 digits)
];

// Create a Bank Account token
$bankToken = Token::create([
    'bank_account' => [
        'country' => $testBankDetails['country'],
        'currency' => $testBankDetails['currency'],
        'account_holder_name' => 'Stripe Test User',
        'account_holder_type' => 'individual',
        'account_number' => $testBankDetails['account_number'],
        'routing_number' => $testBankDetails['routing_number'],
    ],
]);

// Introduce a brief delay (e.g., 2 seconds) to allow token propagation
sleep(2);

// Use the Bank Account token as the destination for a Payout
$payout = Payout::create([
    'amount' => 1000, // Replace with the actual amount you want to transfer (in cents)
    'currency' => 'usd',
    'destination' => 'acct_1ObQMmRasPmNVu4o', // Use the Bank Account token as the destination
]);
// Check the status of the payout
if ($payout->status === 'paid') {
    // Payout was successful
    // You can handle success here
    return "Payout successful!";
} else {
    // Payout failed
    // You can handle the failure here
    return "Payout failed: " . $payout->failure_message;
}

        

        // Stripe::setApiKey("sk_test_51Nu9mBDJ9oRgyjebvyDL1NNHOBjkrZr5iViQNeKjSPWcAG801TmBkQo2mKvcsYDnviyRDFlCU0vF5I85jUPpg01f00p1BpqPeH");
        // // Get the bank account details from the request
        // $bankAccountDetails = [
        //     'account_number' => '000123456789',
        //     'routing_number' => '110000000',
        //     'country' => 'US'
        //     // Add other necessary details
        // ];

        // // Create a bank account token
        // $token = Token::create([
        //     'bank_account' => $bankAccountDetails,
        // ]);

        // // Transfer funds using the bank account token
        // try {
        //     $transfer = Transfer::create([
        //         'amount' => 1000, // Amount in cents
        //         'currency' => 'usd',
        //         'destination' => $token->bank_account, // Use 'destination' instead of 'source'
        //     ]);

        //     // Handle success
        //     return response()->json(['success' => true, 'transfer' => $transfer]);
        // } catch (\Exception $e) {
        //     // Handle error
        //     return response()->json(['success' => false, 'error' => $e->getMessage()]);
        // }






        try {
            $booking = Booking::with('reservation')->where('id', $id)->first();
            
            $bookingAmount = $booking->reservation['amount'];
            
            $sonographerID = $booking->sonographer_id;

            // find sonographer package
            $findPackage = Package::whereHas('clients', function ($query) use ($sonographerID) {
                $query->where('client_id', $sonographerID);
            })->first();


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

                return $selectedBank;
                
                
            } else {
                return sendResponse(false, 200, 'Package not found for sonographer!', [], 200);
            }

            return $packages;
            
            
            $findPackage = Package::find($booking->package_id);
            $packageAmount = $findPackage->payment;

            // minus the payment in cent not in percentage
            $transferAmount = $chargeAmount - $packageAmount;

            // getting bank details of sonographer with high priority
            $findSonographerBank = BankInfo::where('client_id', $booking->sonographer_id)
                ->where('priority', 'high')
                ->first();
            $bankDetails = $findSonographerBank;


            Stripe::setApiKey("sk_test_51Nu9mBDJ9oRgyjebvyDL1NNHOBjkrZr5iViQNeKjSPWcAG801TmBkQo2mKvcsYDnviyRDFlCU0vF5I85jUPpg01f00p1BpqPeH");
            $transfer = Transfer::create([
                'amount' => $transferAmount,
                'currency' => $bankDetails['currency'],
                'destination' => $bankDetails['iban'],
                'source_type' => 'bank_account',
            ]);
        
            // $this->transferToBankAccount($bankDetails, $transferAmount);
            $booking->status = 'Completed';
            $booking->save();
            
            return sendResponse(true, 200, 'Booking Request Completed Successfully!', $booking, 200);
        } catch (\Exception $ex) {
            return sendResponse(false, 500, 'Internal Server Error', $ex->getMessage(), 200);
        }
    }


    private function transferToBankAccount($bankDetails, $transferAmount)
    {
        Stripe::setApiKey("sk_test_51Nu9mBDJ9oRgyjebvyDL1NNHOBjkrZr5iViQNeKjSPWcAG801TmBkQo2mKvcsYDnviyRDFlCU0vF5I85jUPpg01f00p1BpqPeH");
        try {
            // Create a transfer
            $transfer = Transfer::create([
                'amount' => $transferAmount,
                'currency' => $bankDetails['currency'],
                'destination' => $bankDetails['iban'],
                'source_type' => 'bank_account',
            ]);
            return response()->json(['success' => true, 'transfer' => $transfer]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }

        // $customerID = $bankDetails->stripe_token; // Replace with your actual customer ID
        // $customer = \Stripe\Customer::retrieve($customerID);                                                

        // // Create a transfer to the bank account associated with the token
        // $transfer = Transfer::create([
        //     'amount' => $transferAmount, // amount already in cents
        //     'currency' => $bankDetails->currency, // Adjust the currency as needed
        //     'source_type' => 'customer',
        //     'source' => $customerID,
        //     'destination' => $customer->default_source,
        //     'description' => 'Bank transfer from SonoLinq',
        // ]);
    
        // return $transfer;
    }
    // My xhanges in process working


    // Stats API for admin
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

    // Stats API for client
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

    public function directBooking(Request $request) {
        try {
            if($request->input('token')) {
                Stripe::setApiKey("sk_test_51Nu9mBDJ9oRgyjebvyDL1NNHOBjkrZr5iViQNeKjSPWcAG801TmBkQo2mKvcsYDnviyRDFlCU0vF5I85jUPpg01f00p1BpqPeH");
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