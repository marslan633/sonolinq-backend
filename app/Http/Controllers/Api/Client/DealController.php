<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Models\Deal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\Api\Client\StoreDealRequest;
use App\Http\Requests\Api\Client\UpdateDealRequest;


class DealController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $deals = Deal::with('services')
                ->whereIn('status', explode(',', $request->status))
                ->orderBy('id', 'desc')
                ->get();

            return sendResponse(true, 200, 'Deals Fetched Successfully!', $deals, 200);
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
    public function store(StoreDealRequest $request)
    {
        try {
            $id =  Auth::guard('client-api')->user()->id;
            $deal = new Deal();
            $deal->client_id = $id;
            $deal->name = $request->name;
            $deal->price = $request->price;
            $deal->status = true;
            if($request->hasFile('image')) {
                $deal->image = $request->file('image')->store('productImages', 'public');
            }
            $deal->save();
            $deal->products()->attach(json_decode($request->products));

            $dealObj = Deal::with('services')->find($deal->id);
            
            return sendResponse(true, 200, 'Deal Created Successfully!', $dealObj, 200);
        } catch (\Exception $ex) {
            return sendResponse(false, 500, 'Internal Server Error', $ex->getMessage(), 200);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Deal $deal)
    {
        try {
            $deal = Deal::with('services')->find($deal->id);
            return sendResponse(true, 200, 'Category Product Fetched Successfully!', $deal, 200);
        } catch (\Exception $ex) {
            return sendResponse(false, 500, 'Internal Server Error', $ex->getMessage(), 200);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Deal $deal)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateDealRequest $request, Deal $deal)
    {
        try {
            $deal = Deal::find($deal->id);
            $dealRequest = $request->all();
            
            if($request->hasFile('image')) {
                $newImage = $request->file('image')->store('productImages', 'public');
                $dealRequest['image'] = $newImage;
            }
            
            $deal->update($dealRequest);
            if($request->products) {
                $deal->products()->detach();
                $deal->products()->attach(json_decode($dealRequest['products']));
            }
            $dealObj = Deal::with('services')->find($deal->id);
            
            return sendResponse(true, 200, 'Deal Updated Successfully!', $dealObj, 200);
        } catch (\Exception $ex) {
            return sendResponse(false, 500, 'Internal Server Error', $ex->getMessage(), 200);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Deal $deal)
    {
        try {
            $deal = Deal::find($deal->id);

            if ($deal) {
                // Detach and delete the associated products.
                $deal->products()->detach();
                
                // Delete the deal itself.
                $deal->delete();

                return sendResponse(true, 200, 'Deal Deleted Successfully!', [], 200);
            } else {
                return sendResponse(true, 200, 'Deal not found', [], 200);
            }
        } catch (\Exception $ex) {
            return sendResponse(false, 500, 'Internal Server Error', $ex->getMessage(), 200);
        }
    }
}