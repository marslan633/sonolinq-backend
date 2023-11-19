<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    /***
     * get client profile
     *
     * **/
    public function client_profile()
    {
        try {
            $id =  Auth::guard('client-api')->user()->id;
            $client = Client::with('company')->find($id);
            return sendResponse(true, 200, 'Client Fetched Successfully!', $client, 200);
        } catch (\Exception $ex) {
            return sendResponse(false, 500, 'Internal Server Error', $ex->getMessage(), 200);
        }
    }
}