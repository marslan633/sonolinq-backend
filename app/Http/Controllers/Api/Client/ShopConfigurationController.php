<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Models\ShopConfiguration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\Api\Client\StoreShopConfigurationRequest;
use App\Http\Requests\Api\Client\UpdateShopConfigurationRequest;

class ShopConfigurationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $shop = ShopConfiguration::where('client_id', auth()->user()->id)->orderBy('id', 'desc')->get();
            return sendResponse(true, 200, 'Shop configration Fetched Successfully!', $shop, 200);
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
    public function store(StoreShopConfigurationRequest $request)
    {
        try {
            $id =  Auth::guard('client-api')->user()->id;
            $shop = new ShopConfiguration();
            $shop->client_id = $id;
            $shop->name = $request->name;
            if($request->file('logo')) {
                $shop->logo = $request->file('logo')->store('productImages', 'public');
            }
            if( $request->file('banner') ) {
                $shop->banner = $request->file('banner')->store('productImages', 'public');   
            }
            $shop->trade_line = $request->trade_line;
            $shop->phone_number = $request->phone_number;
            $shop->address = $request->address;
            $shop->working_hours = $request->working_hours;
            $shop->save();
            
            return sendResponse(true, 200, 'Shop Configuration Created Successfully!', $shop, 200);
        } catch (\Exception $ex) {
            return sendResponse(false, 500, 'Internal Server Error', $ex->getMessage(), 200);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(ShopConfiguration $shopConfiguration)
    {
        try {
            $product = ShopConfiguration::where('id', $shopConfiguration->id)->first();
            return sendResponse(true, 200, 'Configuration Fetched Successfully!', $product, 200);
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
    public function update(UpdateShopConfigurationRequest $request, ShopConfiguration $shopConfiguration)
    {
        try {
            $object = ShopConfiguration::find($shopConfiguration->id);
            $shop = $request->all();
            
            $shop['client_id'] =  Auth::guard('client-api')->user()->id;
            if($request->file('logo')) {
                $shop->logo = $request->file('logo')->store('productImages', 'public');
            }
            if( $request->file('banner') ) {
                $shop->banner = $request->file('banner')->store('productImages', 'public');   
            }
            $object->update($shop);
            
            $objProduct = ShopConfiguration::where('id', $object->id)->first();
            return sendResponse(true, 200, 'Shop Configuration Updated Successfully!', $objProduct, 200);
        } catch (\Exception $ex) {
            return sendResponse(false, 500, 'Internal Server Error', $ex->getMessage(), 200);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ShopConfiguration $shopConfiguration)
    {
        try {
            ShopConfiguration::destroy($shopConfiguration->id);
            return sendResponse(true, 200, 'Shop Deleted Successfully!', [], 200);
        } catch (\Exception $ex) {
            return sendResponse(false, 500, 'Internal Server Error', $ex->getMessage(), 200);
        }
    }

    public function updateConfiguration(Request $request) {
        try {
            $id = Auth::guard('client-api')->user()->id;
            
            $object = ShopConfiguration::where('client_id', $id)->first();
            $shop = $request->all();
            
            $shop['client_id'] =  $id;
            if($request->file('logo')) {
                $shop->logo = $request->file('logo')->store('productImages', 'public');
            }
            if( $request->file('banner') ) {
                $shop->banner = $request->file('banner')->store('productImages', 'public');   
            }
            $object->update($shop);
            
            $objProduct = ShopConfiguration::where('id', $object->id)->first();
            return sendResponse(true, 200, 'Shop Configuration Updated Successfully!', $objProduct, 200);
        } catch (\Exception $ex) {
            return sendResponse(false, 500, 'Internal Server Error', $ex->getMessage(), 200);
        }
    }

    public function showConfiguration() {
        try {
            $id = Auth::guard('client-api')->user()->id;
            $product = ShopConfiguration::where('client_id', $id)->first();
            return sendResponse(true, 200, 'Shop Configuration Fetched Successfully!', $product, 200);
        } catch (\Exception $ex) {
            return sendResponse(false, 500, 'Internal Server Error', $ex->getMessage(), 200);
        }
    }


    public function getConfiguration($id)
    {
        try {
            $product = ShopConfiguration::find($id);
            return sendResponse(true, 200, 'Configuration Fetched Successfully!', $product, 200);
        } catch (\Exception $ex) {
            return sendResponse(false, 500, 'Internal Server Error', $ex->getMessage(), 200);
        }
    }
}