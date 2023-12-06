<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\BankInfo;

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
}