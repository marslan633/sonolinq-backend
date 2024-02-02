<?php

namespace App\Http\Controllers\Api\Manager;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{SonographerType};
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\Api\Manager\StoreSonographerTypeRequest;
use App\Http\Requests\Api\Manager\UpdateSonographerTypeRequest;

class SonographerTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $sonotypes = SonographerType::whereIn('status', explode(',', $request->status))->orderBy('id', 'desc')->get();
            return sendResponse(true, 200, 'Sonographer Type Fetched Successfully!', $sonotypes, 200);
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
    public function store(StoreSonographerTypeRequest $request)
    {
        try {
            $id = Auth::guard('user-api')->user()->id;
            $sonotype = new SonographerType();
            $sonotype->user_id = $id;
            $sonotype->name = $request->name;
            $sonotype->price = $request->price;
            $sonotype->status = true;
            $sonotype->save();
            
            /*Retruing Response*/   
            return sendResponse(true, 200, 'Sonographer Type Created Successfully!', $sonotype, 200);
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
            $sonotype = SonographerType::find($id);
            return sendResponse(true, 200, 'Sonographer Type Fetched Successfully!', $sonotype, 200);
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
    public function update(UpdateSonographerTypeRequest $request, string $id)
    {
        try {
            $sonotype = SonographerType::find($id);
            $sonotype->update($request->all());
            
            return sendResponse(true, 200, 'Sonographer Type Updated Successfully!', $sonotype, 200);
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
            $sonotype = SonographerType::find($id);
            $sonotype->delete();

            return sendResponse(true, 200, 'Sonographer Type Deleted Successfully!', [], 200);
        } catch (\Exception $ex) {
            return sendResponse(false, 500, 'Internal Server Error', $ex->getMessage(), 200);
        }
    }

    public function getSonographerTypes(Request $request)
    {
        try {
            $sonotypes = SonographerType::whereIn('status', explode(',', $request->status))->orderBy('id', 'desc')->get();
            return sendResponse(true, 200, 'Sonographer Type Fetched Successfully!', $sonotypes, 200);
        } catch (\Exception $ex) {
            return sendResponse(false, 500, 'Internal Server Error', $ex->getMessage(), 200);
        }
    }
}