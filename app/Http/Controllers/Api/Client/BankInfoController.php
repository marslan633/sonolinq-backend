<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\BankInfo;
use Stripe\Stripe;
use Stripe\Customer;
use Stripe\Token;
use Stripe\Transfer;

class BankInfoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $packages = BankInfo::with('client')->where('client_id', auth()->user()->id)->whereIn('status', explode(',', $request->status))->orderBy('id', 'desc')->get();
            return sendResponse(true, 200, 'Bank Info Fetched Successfully!', $packages, 200);
        } catch (\Exception $ex) {
            return sendResponse(false, 500, 'Internal Server Error', $ex->getMessage(), 200);
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {   
            $bank = $request->all();
            $bank['client_id'] =  Auth::guard('client-api')->user()->id;
            
            $bank = BankInfo::create($bank);

            $stripeToken = $this->createStripeToken($bank);
            $bank->update(['stripe_token' => $stripeToken]);

            $bankObj = BankInfo::where('id', $bank->id)->with('client')->first();
            return sendResponse(true, 200, 'Bank Info Created Successfully!', $bankObj, 200);
        } catch (\Exception $ex) {
            return sendResponse(false, 500, 'Internal Server Error', $ex->getMessage(), 200);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            $bank = BankInfo::where('id', $id)->with('client')->first();
            return sendResponse(true, 200, 'Bank Info Fetched Successfully!', $bank, 200);
        } catch (\Exception $ex) {
            return sendResponse(false, 500, 'Internal Server Error', $ex->getMessage(), 200);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, BankInfo $bankInfo)
    {
        try {
            $bankInfo->update($request->all());
            $bankObj = BankInfo::where('id', $bankInfo->id)->with('client')->first();
            return sendResponse(true, 200, 'Bank Info Updated Successfully!', $bankObj, 200);
        } catch (\Exception $ex) {
            return sendResponse(false, 500, 'Internal Server Error', $ex->getMessage(), 200);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(BankInfo $bankInfo)
    {
        try {
            $bankInfo->delete();
            return sendResponse(true, 200, 'Bank Info Deleted Successfully!', [], 200);
        } catch (\Exception $ex) {
            return sendResponse(false, 500, 'Internal Server Error', $ex->getMessage(), 200);
        }
    }

    private function createStripeToken($bankInfo)
    {
        Stripe::setApiKey("sk_test_51Nu9mBDJ9oRgyjebvyDL1NNHOBjkrZr5iViQNeKjSPWcAG801TmBkQo2mKvcsYDnviyRDFlCU0vF5I85jUPpg01f00p1BpqPeH");

        $token = \Stripe\Token::create([
            'bank_account' => [
                'country' => $bankInfo->country, 
                'currency' => $bankInfo->currency,
                'account_holder_name' => $bankInfo->name,
                'account_holder_type' => $bankInfo->account_holder_type,
                'routing_number' => $bankInfo->routing_number,
                'account_number' => $bankInfo->iban,
            ],
        ]);

        return $token->id;
    }
}