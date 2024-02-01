<?php

namespace App\Http\Controllers\Api\Manager;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\{Package};


class PackageController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $packages = Package::with('clients')->whereIn('status', explode(',', $request->status))->orderBy('id', 'desc')->get();
            return sendResponse(true, 200, 'Packages Fetched Successfully!', $packages, 200);
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
            $package = $request->all();
            $package['user_id'] =  Auth::guard('user-api')->user()->id;
            $package = Package::create($package);

            $package->clients()->attach($request->clients);
              
            $packageObj = Package::where('id', $package->id)->with('clients')->first();
            return sendResponse(true, 200, 'Package Created Successfully!', $packageObj, 200);
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
            $package = Package::where('id', $id)->with('clients')->first();
            return sendResponse(true, 200, 'Package Fetched Successfully!', $package, 200);
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
    public function update(Request $request, string $id)
    {
        try {

            $package = Package::findOrFail($id);

            if($request->input('status')) {
                $status = $request->input('status');
            } else {
                $status = true;
            }
            $package->update([
                'user_id' => Auth::guard('user-api')->user()->id,
                'type' => $request->input('type'),
                'payment' => $request->input('payment'),
                'status' => $status,
            ]);
            
            
            $package->clients()->sync($request->clients);

            $objPackage = Package::where('id', $package->id)->with('clients')->first();
            return sendResponse(true, 200, 'Package Updated Successfully!', $objPackage, 200);
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
            $package = Package::findOrFail($id);
            $package->clients()->detach();
            $package->delete();
            return sendResponse(true, 200, 'Package Deleted Successfully!', [], 200);
        } catch (\Exception $ex) {
            return sendResponse(false, 500, 'Internal Server Error', $ex->getMessage(), 200);
        }
    }
}