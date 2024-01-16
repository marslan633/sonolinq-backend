<?php

namespace App\Http\Controllers\Api\Manager;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Manager\UpdateClientRequest;
use App\Models\{Client, Company, Booking, Preference, EligibleSonographer, Reservation, Service};
use App\Models\Configuration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Stripe\Stripe;
use Stripe\Charge;
use DB;
use Illuminate\Support\Facades\Mail;
use App\Mail\BookingRequestMail;

class ClientController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $staff = Client::with('company.type_of_services')->whereIn('status', explode(',', $request->status))->orderBy('id', 'desc')->get();
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
            $client = Client::with('company.type_of_services', 'addresses')->find($id);
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
            $client->update($request->all());
            if ($request->hasFile('non_solicitation_agreement')) {
                $client['non_solicitation_agreement'] = $request->file('non_solicitation_agreement')->store('companyImages', 'public');
                $client->update();
            }
            /*Creating Company*/
            $company = $request->all();
            if ($request->hasFile('reg_no_letter')) {
                $company['reg_no_letter'] = $request->file('reg_no_letter')->store('companyImages', 'public');
                Storage::disk('public')->delete($client->company->reg_no_letter);
            }
            if ($request->hasFile('personal_director_id')) {
                $company['personal_director_id'] = $request->file('personal_director_id')->store('companyImages', 'public');
                Storage::disk('public')->delete($client->company->personal_director_id);
            }
            if ($request->hasFile('prove_of_address')) {
                $company['prove_of_address'] = $request->file('prove_of_address')->store('companyImages', 'public');
                Storage::disk('public')->delete($client->company->prove_of_address);
            }
            if (isset($request->company_name)) {
                $client->company()->update($company);
            }

            if (isset($request->type_of_services)) { 
                $company = $client->company;
                $company->type_of_services()->detach();
                $company->type_of_services()->attach($request->type_of_services);
            }
            
            /*Creating Address*/
            if (isset($request->personal_address)) {
                $client->addresses()->create((array)json_decode($request->personal_address));
            }

            
            // if (isset($request->parcel_return_address)) {
            //     $client->addresses()->create((array)json_decode($request->parcel_return_address));
            // }
            $client = Client::with('company.type_of_services')->find($id);

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


    public function getClient($id) {
        try {
            $client = Client::with('company.type_of_services', 'addresses')->find($id);
            return sendResponse(true, 200, 'Client Fetched Successfully!', $client, 200);
        } catch (\Exception $ex) {
            return sendResponse(false, 500, 'Internal Server Error', $ex->getMessage(), 200);
        }
    }


    public function updateClient(Request $request, String $id)
    {
        try {
            /*Creating Client*/
            $client = Client::find($id);
            $client->update($request->all());
            if ($request->hasFile('non_solicitation_agreement')) {
                $client['non_solicitation_agreement'] = $request->file('non_solicitation_agreement')->store('companyImages', 'public');
                $client->update();
            }
            /*Creating Company*/
            $company = $request->all();
            if ($request->hasFile('reg_no_letter')) {
                $company['reg_no_letter'] = $request->file('reg_no_letter')->store('companyImages', 'public');
                Storage::disk('public')->delete($client->company->reg_no_letter);
            }
            if ($request->hasFile('personal_director_id')) {
                $company['personal_director_id'] = $request->file('personal_director_id')->store('companyImages', 'public');
                Storage::disk('public')->delete($client->company->personal_director_id);
            }
            if ($request->hasFile('prove_of_address')) {
                $company['prove_of_address'] = $request->file('prove_of_address')->store('companyImages', 'public');
                Storage::disk('public')->delete($client->company->prove_of_address);
            }
            if (isset($request->company_name)) {
                $client->company()->update($company);
            }

            if (isset($request->type_of_services)) { 
                $company = $client->company;
                $company->type_of_services()->detach();
                $company->type_of_services()->attach($request->type_of_services);
            }
            
            /*Creating Address*/
            if (isset($request->personal_address)) {
                $client->addresses()->create((array)json_decode($request->personal_address));
            }

            
            // if (isset($request->parcel_return_address)) {
            //     $client->addresses()->create((array)json_decode($request->parcel_return_address));
            // }
            $client = Client::with('company.type_of_services')->find($id);

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
            
            $records = Company::with('client')
                ->whereHas('client', function ($query) {
                    $query->where('role', 'Sonographer');
                })
                ->when($level, function ($query, $level) {
                    $query->where('level', $level);
                })
                ->when($experience, function ($query, $experience) {
                    $query->where('years_of_experience', '>=', $experience);
                })
                ->when($register_no, function ($query) {
                    $query->whereNotNull('register_no');
                })
                ->when($language, function ($query) use ($language) {
                    $query->where(function ($subQuery) use ($language) {
                        foreach ($language as $lang) {
                            $subQuery->orWhere('languages_spoken', 'LIKE', '%' . $lang . '%');
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
                        'amount' => $request->input('amount'), // Amount in cents
                        'currency' => 'usd',
                        'source' => $request->input('token'), // Token received from client
                        'description' => 'SonoLinq Service Payment',
                    ]);
                } catch (\Exception $e) {
                    // Handle payment error here
                    return response()->json(['error' => $e->getMessage()], 500);
                }
            }
            
            $booking = $request->all();
            $client_id = Auth::guard('client-api')->user()->id;
            
            $booking['doctor_id'] = $client_id;
            $booking = Booking::create($booking);

            foreach ($request->reservations as $reservationData) {
                $reservation = Reservation::create([
                    'type' => $reservationData['type'],
                    'date' => $reservationData['date'],
                    'time' => $reservationData['time'],
                    'booking_id' => $booking->id,
                ]);

                $reservation->serviceCategories()->attach($reservationData['service_category_id']);
                $reservation->services()->attach($reservationData['service_id']);
            }

            $preference = $request->all();
            $preference['booking_id']= $booking->id; 
            
            if($request->input('sonographer_language')) {
                $arrayLanguage = $request->input('sonographer_language');
                $strLang = implode(',', $arrayLanguage);
                
                $preference['sonographer_language'] = $strLang;                                 
            }
            $preference = Preference::create($preference);

            // Run eligibility check for sonographer
            $gender = $request->input('sonographer_gender');
            $level = $request->input('sonographer_level');
            $experience = $request->input('sonographer_experience');
            $register_no = $request->input('sonographer_registery');
            $language = $request->input('sonographer_language');
            
            // $records = Company::with('client')
            //     ->whereHas('client', function ($query) {
            //         $query->where('role', 'Sonographer');
            //     })
            //     ->when($gender, function ($query, $gender) {
            //         $query->where('gender', $gender);
            //     })
            //     ->when($level, function ($query, $level) {
            //         $query->where('level', $level);
            //     })
            //     ->when($experience, function ($query, $experience) {
            //         $query->where('years_of_experience', $experience);
            //     })
            //     ->when($register_no, function ($query) {
            //         $query->whereNotNull('register_no');
            //     })
            //     ->when($language, function ($query, $language) {
            //         $query->where('languages_spoken', 'LIKE', '%' . $language . '%');
            //     })
            //     ->pluck('id');
            $records = Company::with('client')
                ->whereHas('client', function ($query) {
                    $query->where('role', 'Sonographer');
                })
                ->when($level, function ($query, $level) {
                    $query->where('level', $level);
                })
                ->when($experience, function ($query, $experience) {
                    $query->where('years_of_experience', '>=', $experience);
                })
                ->when($register_no, function ($query) {
                    $query->whereNotNull('register_no');
                })
                ->when($language, function ($query) use ($language) {
                    $query->where(function ($subQuery) use ($language) {
                        foreach ($language as $lang) {
                            $subQuery->orWhere('languages_spoken', 'LIKE', '%' . $lang . '%');
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
                ->get();

            $sonographers = $records;

            foreach($sonographers as $sonographer) {
                $eligible = new EligibleSonographer();
                $eligible->sonographer_id = $sonographer->client_id;
                $eligible->booking_id = $booking->id;
                $eligible->save();
                // EligibleSonographer::create(
                //     ['sonographer_id' => $sonographer->client_id],
                //     ['booking_id' => $bookingID]
                // );
                Mail::to($sonographer->client['email'])->send(new BookingRequestMail());
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
                'booking.reservations' => function ($query) {
                    $query->with(['serviceCategories', 'services.category']);
                },
                'booking.doctor',
                'booking.sonographer',
            ])
            ->where('sonographer_id', $client_id)
            ->whereIn('status', explode(',', $request->status))
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
            $sonograhper->status = 'Active';
            $sonograhper->save();

            $booking = Booking::find($sonograhper->booking_id);
            $booking->sonographer_id = $sonograhper->sonographer_id;
            $booking->status = 'Active';
            $booking->save();

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

    public function bookingList() {
        try {
            // $bookings = Booking::with('service_category')->with('service')->with('doctor')->with('sonographer')->with('preferences')->get();
            $bookings = Booking::with([
                'reservations' => function ($query) {
                    $query->with(['serviceCategories', 'services']);
                },
                'doctor',
                'sonographer',
                'preferences',
            ])
            ->get();
            return sendResponse(true, 200, 'Booking List Fetched Successfully!', $bookings, 200);
        } catch (\Exception $ex) {
            return sendResponse(false, 500, 'Internal Server Error', $ex->getMessage(), 200);
        }
    }

    public function getDoctorBookings(Request $request) {
        try {
            // $bookings = Booking::where('doctor_id', Auth::user()->id)->whereIn('status', explode(',', $request->status))->with('service_category')->with('service')->with('doctor')->with('sonographer')->with('preferences')->get();
            $bookings = Booking::where('doctor_id', Auth::user()->id)
                ->whereIn('status', explode(',', $request->status))
                ->with([
                    'reservations' => function ($query) {
                        $query->with(['serviceCategories', 'services.category']);
                    },
                    'sonographer',
                    'preferences',
                    'doctor'
                ])
                ->get();
            return sendResponse(true, 200, 'Booking List Fetched Successfully!', $bookings, 200);
        } catch (\Exception $ex) {
            return sendResponse(false, 500, 'Internal Server Error', $ex->getMessage(), 200);
        }
    }

    public function showBooking($id) {
        try {
            // $bookings = Booking::where('id', $id)->with('service_category')->with('service')->with('doctor')->with('sonographer')->with('preferences')->get();

            $bookings = Booking::where('id', $id)
                ->with([
                    'reservations' => function ($query) {
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
                return sendResponse(false, 401, 'Token is not valid, Please login again!', [], 200);
            }
        } catch (\Exception $ex) {
            return sendResponse(false, 500, 'Internal Server Error', $ex->getMessage(), 200);
        }
    }
}