<?php

namespace App\Http\Controllers\Api\Manager;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{Equipment};
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\Api\Manager\StoreEquipmentRequest;
use App\Http\Requests\Api\Manager\UpdateEquipmentRequest;

class EquipmentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $equ = Equipment::whereIn('status', explode(',', $request->status))->orderBy('id', 'desc')->get();
            return sendResponse(true, 200, 'Equipment Fetched Successfully!', $equ, 200);
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
    public function store(StoreEquipmentRequest $request)
    {
        try {
            $id = Auth::guard('user-api')->user()->id;
            $equ = new Equipment();
            $equ->user_id = $id;
            $equ->name = $request->name;
            $equ->status = true;
            $equ->save();
            
            /*Retruing Response*/   
            return sendResponse(true, 200, 'Equipment Created Successfully!', $equ, 200);
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
            $equ = Equipment::find($id);
            return sendResponse(true, 200, 'Equipment Fetched Successfully!', $equ, 200);
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
    public function update(UpdateEquipmentRequest $request, string $id)
    {
        try {
            $equ = Equipment::find($id);
            $equ->update($request->all());
            
            return sendResponse(true, 200, 'Equipment Updated Successfully!', $equ, 200);
        } catch (\Exception $ex) {
            return sendResponse(false, 500, 'Internal Server Error', $ex->getMessage(), 200);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $equ = Equipment::find($id);
            $equ->delete();

            return sendResponse(true, 200, 'Equipment Deleted Successfully!', [], 200);
        } catch (\Exception $ex) {
            return sendResponse(false, 500, 'Internal Server Error', $ex->getMessage(), 200);
        }
    }

    public function getLanguages(Request $request) { 
        try {
            $equ = Equipment::whereIn('status', explode(',', $request->status))->orderBy('id', 'desc')->get();
            return sendResponse(true, 200, 'Equipment Fetched Successfully!', $equ, 200);
        } catch (\Exception $ex) {
            return sendResponse(false, 500, 'Internal Server Error', $ex->getMessage(), 200);
        }
    }
}