<?php

namespace App\Http\Controllers\Api\Manager;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Manager\StoreStaffRequest;
use App\Http\Requests\Api\Manager\UpdateStaffRequest;
use App\Mail\StaffRegisterMail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class StaffController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $staff = User::where('id', '!=', auth()->user()->id)->orderBy('id', 'desc')->get();
            return sendResponse(true, 200, 'Staff Fetched Successfully!', $staff, 200);
        } catch (\Exception $ex) {
            return sendResponse(false, 500, 'Internal Server Error', $ex->getMessage(), 200);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreStaffRequest $request)
    {
        try {
            /*Creating Staff*/
            $staff = User::create();
            /*Sending Register Mail*/
            Mail::to($request->email)->send(new StaffRegisterMail(['details' => $request->all()]));
            /*Retruing Response*/
            return sendResponse(true, 200, 'Staff Created Successfully!', $staff, 200);
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
            $user = User::find($id);
            return sendResponse(true, 200, 'Staff Fetched Successfully!', $user, 200);
        } catch (\Exception $ex) {
            return sendResponse(false, 500, 'Internal Server Error', $ex->getMessage(), 200);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateStaffRequest $request, String $id)
    {

        try {
            $user = User::find($id);
            $data = $request->all();
            if (isset($request->email) && $request->email != $user->email) {
                /*Generating Password*/
                $data['password'] = Str::random(8, '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ');
                /*Sending Register Mail*/
                Mail::to($request->email)->send(new StaffRegisterMail(['details' => $data]));
            }
            $user->update($request->all());
            return sendResponse(true, 200, 'User Updated Successfully!', $user, 200);
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
            User::destroy($id);
            return sendResponse(true, 200, 'User Deleted Successfully!', [], 200);
        } catch (\Exception $ex) {
            return sendResponse(false, 500, 'Internal Server Error', $ex->getMessage(), 200);
        }
    }
}
