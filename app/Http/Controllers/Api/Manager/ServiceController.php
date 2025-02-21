<?php

namespace App\Http\Controllers\Api\Manager;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\{Service};
use App\Http\Requests\Api\Client\StoreProductRequest;
use App\Http\Requests\Api\Client\UpdateProductRequest;
use Illuminate\Support\Facades\Auth;

class ServiceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
{
    try {
        $query = Service::query();

        // Filter by status if provided
        if ($request->has('status') && !empty($request->status)) {
            $query->whereIn('status', explode(',', $request->status));
        }

        $services = $query->orderBy('id', 'desc')->get();

        return sendResponse(true, 200, 'Services Fetched Successfully!', $services, 200);
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
    public function store(StoreProductRequest $request)
    {
        try {
            $service = $request->all();
            $service['user_id'] =  Auth::guard('user-api')->user()->id;
            $service['status'] = true;
            $service['category_id'] = $request->category;

            $service = Service::create($service);

            $objService = Service::where('id', $service->id)->with('category')->first();
            /*Retruing Response*/
            return sendResponse(true, 200, 'Service Created Successfully!', $objService, 200);
        } catch (\Exception $ex) {
            return sendResponse(false, 500, 'Internal Server Error', $ex->getMessage(), 200);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Service $service)
    {
        try {
            $service = Service::where('id', $service->id)->with('category')->first();
            return sendResponse(true, 200, 'Service Fetched Successfully!', $service, 200);
        } catch (\Exception $ex) {
            return sendResponse(false, 500, 'Internal Server Error', $ex->getMessage(), 200);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Service $service)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateProductRequest $request, Service $service)
    {
        try {
            $object = Service::find($service->id);
            $service = $request->all();
            $service['user_id'] =  Auth::guard('user-api')->user()->id;
            if($request->category) {
                $service['category_id'] = $request->category;
            }
            if($request->status) {
                $service['status'] = filter_var($service['status'], FILTER_VALIDATE_BOOLEAN);
            }
            $object->update($service);

            $objService = Service::where('id', $object->id)->with('category')->first();
            return sendResponse(true, 200, 'Service Updated Successfully!', $objService, 200);
        } catch (\Exception $ex) {
            return sendResponse(false, 500, 'Internal Server Error', $ex->getMessage(), 200);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Service $service)
    {
        try {
            Service::destroy($service->id);
            return sendResponse(true, 200, 'Service Deleted Successfully!', [], 200);
        } catch (\Exception $ex) {
            return sendResponse(false, 500, 'Internal Server Error', $ex->getMessage(), 200);
        }
    }


    public function getServices(Request $request) {
        try {

           $query = Service::with('category'); // Include category relationship

            // Filter by status if provided
            if ($request->has('status') && !empty($request->status)) {
                $query->whereIn('status', explode(',', $request->status));
            }

            $services = $query->orderBy('id', 'desc')->get();

            return sendResponse(true, 200, 'Services Fetched Successfully!', $services, 200);

        } catch (\Exception $ex) {
            return sendResponse(false, 500, 'Internal Server Error', $ex->getMessage(), 200);
        }
    }
}
