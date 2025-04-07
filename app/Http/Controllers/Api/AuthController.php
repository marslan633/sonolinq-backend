<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ApiForgotPasswordRequest;
use App\Http\Requests\Api\LoginRequest;
use App\Http\Requests\Api\RegisterClientRequest;
use App\Mail\ForgotPasswordMail;
use App\Mail\VerificationMail;
use App\Mail\DynamicMail;
use App\Models\{User, Client, Registry, EmailTemplate, LevelSystem};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    /**
     * Login Manager Api
     * **/
    public function login(LoginRequest $request){
        try{
            $type = $request->type;
            if(Auth::guard($type)->attempt($request->except(['type', 'device_token']))){
                $user = Auth::guard($type)->user();
                /*Update device_token if provided in the request*/
                if ($request->has('device_token')) {
                    $user->device_token = $request->device_token;
                    $user->save();
                }
                /*Checking Staff Status*/
                if(!$user->status && $type == 'user') return sendResponse(false, 401, 'Your account is not active. please contact support.', [], 200);
                /*Checking Client Email Verificatoin*/
                if($user->is_verified == false && $type == 'client') return sendResponse(false, 401, 'Your email is not verified. Please check your email.', [], 200);

                /*Checking Client Status*/
                if($user->status != 'Active' && $type == 'client') {
                    if ($user->status == 'Suspended') {
                        // Check if the user type is 'client' and then load the relationship
                        if ($type == 'client') {
                            $user->load('level_system');
                        }
                        $accessToken =  $user->createToken('Personal Access Token', [$type])->accessToken;
                        return sendResponse(true, 200, 'Login Successfully!', ['user' => $user, 'token' => $accessToken], 200);
                    } else {
                        return sendResponse(false, 401, $user->status == 'Pending' ? 'Your account is under review we will contact you with an approval email.' : 'Your account is not active. please contact support.', [], 200);
                    }
                }

                // Check if the user type is 'client' and then load the relationship
                if ($type == 'client') {
                    $user->load('level_system');
                }
                $accessToken =  $user->createToken('Personal Access Token', [$type])->accessToken;
                return sendResponse(true, 200, 'Login Successfully!', ['user' => $user, 'token' => $accessToken], 200);
            }else{
                return sendResponse(false, 400, 'Invalid Email or Password', [], 200);
            }
        }catch(\Exception $ex){
            return sendResponse(false, 500, 'Internal Server Error', $ex->getMessage(), 200);
        }
    }

    /**
     * Check Email Api
     * **/
    public function check_email(Request $request){
        try{
            $user = Client::where('email', $request->email)->first();
            if(!is_null($user)) return sendResponse(false, 400, 'Email Exists', [], 200);
            return sendResponse(true, 200, 'Good To Go', [], 200);
        }catch(\Exception $ex){
            return sendResponse(false, 500, 'Internal Server Error', $ex->getMessage(), 200);
        }
    }

    /**
     * Forgot Password Api
     * **/
    public function forgot_password(ApiForgotPasswordRequest $request){
        try{

            $user = $request->type == 'user'
                    ? User::where('email', $request->email)->first()
                    : Client::where('email', $request->email)->first();

            if(is_null($user)) return sendResponse(false, 400, 'Not Email Found.', [], 200);

            $randomPassword = Str::random(8);
            $user->password = $randomPassword;
            $user->update();

            // $emailTemplate = EmailTemplate::where('type', 'forgot-password')->first();

            // $details = [
            //     'subject' => $emailTemplate->subject,
            //     'body'=> $emailTemplate->body,
            //     'type' => $emailTemplate->type,
            //     'full_name' => $user->full_name,
            //     'password' => $randomPassword,
            //     'url' => $request->url,
            // ];

            // /*Sending Register Mail*/
            // Mail::to($request->email)->send(new ForgotPasswordMail($details));

            $emailTemplate = EmailTemplate::where('type', 'forgot-password')->first();
            if($emailTemplate) {
                $details = [
                    'subject' => $emailTemplate->subject,
                    'body'=> $emailTemplate->body,
                    'type' => $emailTemplate->type,
                    'full_name' => $user->full_name,
                    'password' => $randomPassword,
                    'url' => $request->url,
                ];
                /*Sending Register Mail*/
                Mail::to($request->email)->send(new DynamicMail($details));
            }

            return sendResponse(true, 200, 'We just sent you a new password on this email', [], 200);


        }catch(\Exception $ex){
            return sendResponse(false, 500, 'Internal Server Error', $ex->getMessage(), 200);
        }
    }

    /**
     * Register Manager Api
     * **/
    public function verification_code(Request $request, $token){
        try{
            $user = Client::where('email', decrypt_value($token))->first();

            if(is_null($user)) return sendResponse(false, 400, 'Something went wrong', [], 200);

            if($user->is_verified == 1) return sendResponse(false, 400, 'Link Expired', [], 200);

            $user->is_verified = 1;

            // Assign Level 0 to client
            $findLevel = LevelSystem::where('level', 'Level 0')->first();
            if($findLevel) {
                $user->level_system = $findLevel->id;
            }
            $user->update();

            // Send Welcome Email
            $emailTemplate = EmailTemplate::where('type', 'welcome')->first();

            if($emailTemplate) {
                $details = [
                    'subject' => $emailTemplate->subject,
                    'body'=> $emailTemplate->body,
                    'type' => $emailTemplate->type,
                    'full_name' => $user->full_name,
                    'url' => $request->url,
                ];
                Mail::to($user->email)->send(new DynamicMail($details));
            }

            return sendResponse(true, 200, 'Account Verified Successfully!', [], 200);

        }catch(\Exception $ex){
            return sendResponse(false, 500, 'Internal Server Error', $ex->getMessage(), 200);
        }
    }

    /**
     * Register Manager Api
     * **/
    public function register(RegisterClientRequest $request){
        try{
            /*Creating Client*/

            $client = Client::create($request->all());
            if ($request->hasFile('non_solicitation_agreement')) {
                $client['non_solicitation_agreement'] = $request->file('non_solicitation_agreement')->store('companyImages', 'public');
                $client->save();
            }
            /*Creating Company*/
            $company = $request->all();


            if ($request->hasFile('personal_director_id')) {
                $company['personal_director_id'] = $request->file('personal_director_id')->store('companyImages', 'public');
            }
            if ($request->hasFile('prove_of_address')) {
                $company['prove_of_address'] = $request->file('prove_of_address')->store('companyImages', 'public');
            }

            $client->company()->create($company);

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

            if (isset($request->type_of_sonograms)) {
                $company = $client->company;
                $company->type_of_sonograms()->attach(json_decode($request->type_of_sonograms));
            }
            /*Creating Address*/
            $client->addresses()->create((array)json_decode($request->personal_address));
            // $client->addresses()->create((array)json_decode($request->parcel_return_address));

            /*Update reg_no*/
            $client->update(['reg_no' => generateClientId($client->id)]);

            /*Sending Client Verification Mail*/

            // $emailTemplate = EmailTemplate::where('type', 'verification')->first();
            // $details = [
            //     'subject' => $emailTemplate->subject,
            //     'body'=> $emailTemplate->body,
            //     'full_name' => $request->full_name,
            //     'url' => $request->url . encrypt_value($request->email),
            //     'type' => $emailTemplate->type
            // ];
            // Mail::to($request->email)->send(new VerificationMail($details));

            // Send Dynamic Verification Email
            $emailTemplate = EmailTemplate::where('type', 'verification')->first();
            if($emailTemplate) {
                $details = [
                    'subject' => $emailTemplate->subject,
                    'body'=> $emailTemplate->body,
                    'full_name' => $request->full_name,
                    'url' => $request->url . encrypt_value($request->email),
                    'type' => $emailTemplate->type
                ];
                Mail::to($request->email)->send(new DynamicMail($details));
            }

            $emailTemplate = EmailTemplate::where('type', 'new-user-admin-email')->first();

            if($emailTemplate) {

                $adminUsers = User::where('role', 'Admin')->get();
                
                $details = [
                    'subject' => $emailTemplate->subject,
                    'body' => $emailTemplate->body,
                    'username' => $request->full_name,
                    'user_email' => $request->email,
                    'type' => $emailTemplate->type,
                ];
                
                foreach ($adminUsers as $admin) {
                    Mail::to($admin->email)->send(new DynamicMail($details));
                }
            }

            return sendResponse(true, 200, 'Client Registered Successfully!', [], 200);

        }catch(\Exception $ex){
            return sendResponse(false, 500, 'Internal Server Error', $ex->getMessage(), 200);
        }
    }


    /**
     * Change Password Api For User
     * ***/
    public function change_password(Request $request){
        try{
            $user =  $request->type == 'user' ? User::where('id', Auth::guard('user-api')->user()->id)->first() : Client::where('id', Auth::guard('client-api')->user()->id)->first();

            $checkOldPassword = Hash::check($request->oldPassword, $user->password);
            $checkIfOldAndNewPasswordAreSame = Hash::check($request->newPassword, $user->password);

            if(!$checkOldPassword) return sendResponse(false, 400, 'Your current password does not match your old password.', [], 200);
            if(!$checkIfOldAndNewPasswordAreSame) return sendResponse(false, 400, 'A new password cannot be the same as the current password.', [], 200);

            $user->password = $request->newPassword;
            $user->update();
            return sendResponse(true, 200, 'Password updated succcessfully!.', [], 200);
        }catch(\Exception $ex){
            return sendResponse(false, 500, 'Internal Server Error', $ex->getMessage(), 200);
        }
    }


    /**
     * Logout Api
     * **/
    public function logout(Request $request)
    {
        $token = $request->user()->token();
        $token->revoke();

        return response()->json(['message' => 'Logged out successfully']);
    }
}