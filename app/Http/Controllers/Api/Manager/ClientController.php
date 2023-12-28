<?php

namespace App\Http\Controllers\Api\Manager;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Manager\UpdateClientRequest;
use App\Models\{Client, Company, Booking, Preference, EligibleSonographer};
use App\Models\Configuration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Stripe\Stripe;
use Stripe\Charge;

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
            
            // $query = Company::query();
            // if ($gender) {
            //     $query->where('gender', $gender);
            // }

            // if ($level) {
            //     $query->where('level', $level);
            // }

            // if ($experience) {
            //     $query->where('years_of_experience', $experience);
            // }

            // if($register_no) {
            //     $query->whereNotNull('register_no');
            // }

            // if ($language) {
            //     $query->where('languages_spoken', 'LIKE', '%' . $language . '%');
            // }

            // $records = $query->count();

            $records = Company::with('client')
                ->whereHas('client', function ($query) {
                    $query->where('role', 'Sonographer');
                })
                ->when($gender, function ($query, $gender) {
                    $query->where('gender', $gender);
                })
                ->when($level, function ($query, $level) {
                    $query->where('level', $level);
                })
                ->when($experience, function ($query, $experience) {
                    $query->where('years_of_experience', $experience);
                })
                ->when($register_no, function ($query) {
                    $query->whereNotNull('register_no');
                })
                ->when($language, function ($query, $language) {
                    $query->where('languages_spoken', 'LIKE', '%' . $language . '%');
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
                Stripe::setApiKey(env('STRIPE_SECRET'));
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

            $preference = $request->all();
            $preference['booking_id']= $booking->id; 
            $preference = Preference::create($preference);

            // Run eligibility check for sonographer
            $gender = $request->input('sonographer_gender');
            $level = $request->input('sonographer_level');
            $experience = $request->input('sonographer_experience');
            $register_no = $request->input('sonographer_registery');
            $language = $request->input('sonographer_language');
            
            $records = Company::with('client')
                ->whereHas('client', function ($query) {
                    $query->where('role', 'Sonographer');
                })
                ->when($gender, function ($query, $gender) {
                    $query->where('gender', $gender);
                })
                ->when($level, function ($query, $level) {
                    $query->where('level', $level);
                })
                ->when($experience, function ($query, $experience) {
                    $query->where('years_of_experience', $experience);
                })
                ->when($register_no, function ($query) {
                    $query->whereNotNull('register_no');
                })
                ->when($language, function ($query, $language) {
                    $query->where('languages_spoken', 'LIKE', '%' . $language . '%');
                })
                ->pluck('id');

            $sonographerIds = $records;

            foreach($sonographerIds as $sonographerId) {
                EligibleSonographer::updateOrCreate(
                    ['sonographer_id' => $sonographerId],
                    ['booking_id' => $booking->id]
                );
            }
            
            return sendResponse(true, 200, 'Appointment Created Successfully!', $preference, 200);
        } catch (\Exception $ex) {
            return sendResponse(false, 500, 'Internal Server Error', $ex->getMessage(), 200);
        }
    }

    public function getEligibleSonographers(Request $request) {
        try {
            $client_id = Auth::guard('client-api')->user()->id;
            $bookingList = EligibleSonographer::with('booking.service', 'booking.service_category', 'booking.doctor', 'booking.sonographer')->where('sonographer_id', $client_id)->whereIn('status', explode(',', $request->status))->orderBy('id', 'desc')->get();
        
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
            
            $client_id = Auth::guard('client-api')->user()->id;
            EligibleSonographer::where('sonographer_id', '!=', $client_id)->delete();

            $booking = Booking::find($sonograhper->booking_id);
            $booking->sonographer_id = $sonograhper->sonographer_id;
            $booking->save();
            
            return sendResponse(true, 200, 'Sonographer Accept Request Successfully!', $sonograhper, 200);
        } catch (\Exception $ex) {
            return sendResponse(false, 500, 'Internal Server Error', $ex->getMessage(), 200);
        }
    }

    public function bookingList() {
        try {
            $bookings = Booking::with('service_category')->with('service')->with('doctor')->with('sonographer')->with('preferences')->get();
            return sendResponse(true, 200, 'Booking List Fetched Successfully!', $bookings, 200);
        } catch (\Exception $ex) {
            return sendResponse(false, 500, 'Internal Server Error', $ex->getMessage(), 200);
        }
    }
}