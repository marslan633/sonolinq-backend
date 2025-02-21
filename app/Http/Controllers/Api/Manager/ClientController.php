<?php

namespace App\Http\Controllers\Api\Manager;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Manager\UpdateClientRequest;
use App\Models\{Client, Company, Booking, Preference, EligibleSonographer, Reservation, Service, BankInfo, Package, ServiceCategory, Registry, LevelSystem, Review, EmailTemplate, NotificationHistory, Transaction, ConnectedAccount};
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
use Stripe\StripeClient;
use Exception;

class ClientController extends Controller
{
    use NotificationTrait;
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $staff = Client::with('company.type_of_sonograms', 'company.registries', 'addresses', 'package')
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
            $client = Client::with('company.type_of_sonograms', 'company.registries', 'addresses', 'package')->find($id);
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

            if ($request->status === 'Active' && $previousStatus === 'Pending') {
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

            if ($client && in_array($client->status, ['Deactive', 'Rejected', 'Suspended'])) {
                // Send email to the client
                $emailTemplate = EmailTemplate::where('type', 'inactive-client-email')->first();
                if($emailTemplate) {
                    $details = [
                        'subject' => $client->status." ".$emailTemplate->subject,
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


    /**
     * Account Creation, Verification, Transfers and Transactions History API's.
     */

    public function createConnectAccount(Request $request) {
        try {
            Stripe::setApiKey(config('services.stripe.secret'));

            // Create connected account
            $account = \Stripe\Account::create([
                'type' => 'custom',
                'country' => $request->country, // Required: country code of the account
                'business_type' => $request->business_type, // Required: type of business (individual, company)
                'capabilities' => [
                    'card_payments' => ['requested' => true],
                    'transfers' => ['requested' => true],
                ],
                'individual' => [
                    'first_name' => $request->first_name, // Required: first name of the individual
                    'last_name' => $request->last_name, // Required: last name of the individual
                    'email' => $request->email, // Required: email address of the individual
                    'dob' => [
                        'day' => $request->dob_day, // Required: day of birth
                        'month' => $request->dob_month, // Required: month of birth
                        'year' => $request->dob_year, // Required: year of birth
                    ],

                ],
                'tos_acceptance' => [
                    'date' => time(), // Required: current timestamp
                    'ip' => $request->ip(), // Required: IP address of the account holder
                ],
            ]);

            // Determine the status based on different fields in the Stripe account object
            $status = '';

            if ($account->charges_enabled && $account->payouts_enabled) {
                $status = 'Verified';
            } elseif (!$account->charges_enabled && !$account->payouts_enabled) {
                $status = 'Unverified';
            } else {
                $status = 'Pending';
            }

            // Create connected account record
            $connectedAccount = new ConnectedAccount();
            $connectedAccount->account_id = $account->id;
            $connectedAccount->status = $status;
            $connectedAccount->client_id = Auth::guard('client-api')->user()->id;
            $connectedAccount->save();

            return sendResponse(true, 200, 'Connect Account Created, Please verify your account!', $account, 200);
        } catch (InvalidRequestException $e) {
            return sendResponse(false, 500, 'Internal Server Error', $e->getMessage(), 200);
        }
    }

    public function verifyConnectAccount(Request $request) {
        try {
            $stripe = new StripeClient(config('services.stripe.secret'));
            $accountId = $request->connect_account_id;
            $accountSession = $stripe->accountSessions->create([
                'account' => $accountId, // connected_account_id
                'components' => [
                    'account_onboarding' => [
                        'enabled' => true,
                        'features' => ['external_account_collection' => true],
                    ]
                ],
            ]);

            $data = [
                'client_secret' => $accountSession->client_secret,
            ];

            return sendResponse(true, 200, 'Account Session Generate For Account Verification', $data, 200);

        } catch (Exception $e) {
            error_log("An error occurred when calling the Stripe API to create an account session: {$e->getMessage()}");
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function connectAccountVerification(Request $request) {
        try {
            $accountId = $request->connect_account_id;
            // Fetch the created connected account from Stripe to get status
            $account = \Stripe\Account::retrieve($accountId);

            if ($account->charges_enabled && $account->payouts_enabled) {
                $status = 'Verified';
            } elseif (!$account->charges_enabled && !$account->payouts_enabled) {
                $status = 'Unverified';
            } else {
                $status = 'Pending';
            }

            $findAccount = ConnectedAccount::where('account_id', $accountId)->first();
            $findAccount->status = $status;
            $findAccount->update();

            return sendResponse(true, 200, 'Account Status!', $findAccount, 200);
        } catch (\Exception $ex) {
            return sendResponse(false, 500, 'Internal Server Error', $ex->getMessage(), 200);
        }
    }

    public function getConnectAccounts() {
        try {
            $id = Auth::guard('client-api')->user()->id;
            $accounts = ConnectedAccount::where('client_id', $id)->orderBy('id', 'desc')
                ->get();

            foreach($accounts as $account) {
                $stripeAccount = \Stripe\Account::retrieve($account->account_id);
                if ($stripeAccount->charges_enabled && $stripeAccount->payouts_enabled) {
                    $status = 'Verified';
                } elseif (!$stripeAccount->charges_enabled && !$stripeAccount->payouts_enabled) {
                    $status = 'Unverified';
                } else {
                    $status = 'Pending';
                }

                $account->status = $status;
                $account->update();
            }

            return sendResponse(true, 200, 'Accounts Fetched Successfully!', $accounts, 200);
        } catch (\Exception $ex) {
            return sendResponse(false, 500, 'Internal Server Error', $ex->getMessage(), 200);
        }
    }

    public function withdrawalAmount(Request $request)
    {
        try {
            $client = Auth::guard('client-api')->user();

            // Check if user has sufficient balance
            if ($client->virtual_balance < $request->amount) {
                return sendResponse(false, 200, 'Insufficient balance', [], 200);
            }

            // Begin a database transaction
            DB::beginTransaction();

            // Deduct withdrawal amount from user's balance
            $client->virtual_balance -= $request->amount;
            $client->save();


            // Get recipient's Stripe account ID
            $recipientAccountId = $request->input('recipient_account_id');

            // Retrieve account information
            $account = \Stripe\Account::retrieve($recipientAccountId);

            // Check verification status
            if ($account->charges_enabled && $account->details_submitted) {
                // Create transfer
                $transfer = Transfer::create([
                    'amount' => $request->amount * 100, // Amount in cents
                    'currency' => 'usd',
                    'destination' => $recipientAccountId,
                ]);

                // Commit the transaction if transfer is successful
                if ($transfer) {
                    // Record transaction
                    $transactionId = $transfer->id;

                    $transaction = Transaction::create([
                        'client_id' => $client->id,
                        'transaction_id' => $transactionId,
                        'amount' => $request->amount,
                        'type' => 'withdrawal',
                        'created_at' => now(),
                    ]);

                    DB::commit();
                    return sendResponse(true, 200, 'Withdrawal successful!', [], 200);
                } else {
                    // Rollback the transaction if transfer fails
                    DB::rollback();
                    return sendResponse(false, 200, 'Withdrawal failed!', [], 200);
                }
            } else {
                // Rollback the transaction if account is not verified
                DB::rollback();
                return sendResponse(false, 200, 'Recipient account is not verified', [], 200);
            }
        } catch (\InvalidRequestException $ex) {
            // Rollback the transaction in case of any exception
            DB::rollback();
            return sendResponse(false, 500, 'Internal Server Error', $ex->getMessage(), 200);
        }
    }

    public function transactionsHistory() {
        try {
            $id =  Auth::guard('client-api')->user()->id;
            $transactions = Transaction::where('client_id', $id)->orderBy('id', 'desc')->get();
            return sendResponse(true, 200, 'Transactions Fetched Successfully!', $transactions, 200);
        } catch (\Exception $ex) {
            return sendResponse(false, 500, 'Internal Server Error', $ex->getMessage(), 200);
        }
    }

    /**
     * Stripe Connected Account Standard API's
     */
    public function createStripeConnectedAccount(Request $request) {
        try {
            $frontFilePath = $request->file('document_front')->getRealPath();
            $backFilePath = $request->file('document_back')->getRealPath();
            $frontFileContents = Storage::get($frontFilePath);
            $backFileContents = Storage::get($backFilePath);

            // Set your Stripe secret key
            Stripe::setApiKey(config('services.stripe.secret'));
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
    /**
     * Stripe Connected Account Verification for OnBoarding UI (Session Creation).
     */
    public function createAccountSession(Request $request)
    {
        try {
            $stripe = new StripeClient(config('services.stripe.secret'));

            $account_session = $stripe->accountSessions->create([
                'account' => $request->input('connected_account_id'), // assuming connected_account_id is passed through the request
                'components' => [
                    'account_onboarding' => [
                    'enabled' => true,
                    'features' => ['external_account_collection' => true],
                    ]
                ],
            ]);

            return response()->json([
                'client_secret' => $account_session->client_secret
            ]);
        } catch (Exception $e) {
            error_log("An error occurred when calling the Stripe API to create an account session: {$e->getMessage()}");
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function completedBookingRequestStripe($id) {
// Set your Stripe secret key
Stripe::setApiKey(config('services.stripe.secret'));

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



        // Stripe::setApiKey(config('services.stripe.secret'));
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




    }


    private function transferToBankAccount($bankDetails, $transferAmount)
    {
        Stripe::setApiKey(config('services.stripe.secret'));
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
}
