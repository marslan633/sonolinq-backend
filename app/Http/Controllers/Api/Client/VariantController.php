<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Models\Variant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\Api\Client\StoreVariantRequest;
use App\Http\Requests\Api\Client\UpdateVariantRequest;

class VariantController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $variant = Variant::whereIn('status', explode(',', $request->status))->orderBy('id', 'desc')->get();
            return sendResponse(true, 200, 'Variants Fetched Successfully!', $variant, 200);
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
    public function store(StoreVariantRequest $request)
    {
        try {
            $id =  Auth::guard('client-api')->user()->id;
            $variant = new Variant();
            $variant->client_id = $id;
            $variant->name = $request->name;
            if($request->unit) {
                $variant->unit = $request->unit;
            }
            $variant->status = true;
            $variant->save();
            
            /*Retruing Response*/   
            return sendResponse(true, 200, 'Variant Created Successfully!', $variant, 200);
        } catch (\Exception $ex) {
            return sendResponse(false, 500, 'Internal Server Error', $ex->getMessage(), 200);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Variant $variant)
    {
        try {
            $variant = Variant::where('id', $variant->id)->first();
            return sendResponse(true, 200, 'Variant Fetched Successfully!', $variant, 200);
        } catch (\Exception $ex) {
            return sendResponse(false, 500, 'Internal Server Error', $ex->getMessage(), 200);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Variant $variant)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateVariantRequest $request, Variant $variant)
    {
        try {
            $variant = Variant::find($variant->id);
            $variant->update($request->all());
            return sendResponse(true, 200, 'Variant Updated Successfully!', $variant, 200);
        } catch (\Exception $ex) {
            return sendResponse(false, 500, 'Internal Server Error', $ex->getMessage(), 200);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Variant $variant)
    {
        try {
            Variant::destroy($variant->id);
            return sendResponse(true, 200, 'Variant Deleted Successfully!', [], 200);
        } catch (\Exception $ex) {
            return sendResponse(false, 500, 'Internal Server Error', $ex->getMessage(), 200);
        }
    }
}