<?php

namespace App\Http\Controllers\Api\Manager;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{Sonogram};
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\Api\Manager\StoreSonogramRequest;
use App\Http\Requests\Api\Manager\UpdateSonogramRequest;

class SonogramController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $sonotimes = Sonogram::whereIn('status', explode(',', $request->status))->orderBy('id', 'desc')->get();
            return sendResponse(true, 200, 'Sonogram Fetched Successfully!', $sonotimes, 200);
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
    public function store(StoreSonogramRequest $request)
    {
        try {
            $id = Auth::guard('user-api')->user()->id;
            $sonotime = new Sonogram();
            $sonotime->user_id = $id;
            $sonotime->name = $request->name;
            $sonotime->status = true;
            $sonotime->save();

            /*Retruing Response*/
            return sendResponse(true, 200, 'Sonogram Created Successfully!', $sonotime, 200);
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
            $sonotime = Sonogram::find($id);
            return sendResponse(true, 200, 'Sonogram Fetched Successfully!', $sonotime, 200);
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
    public function update(UpdateSonogramRequest $request, string $id)
    {
        try {
            $sonotime = Sonogram::find($id);
            $sonotime->update($request->all());

            return sendResponse(true, 200, 'Sonogram Updated Successfully!', $sonotime, 200);
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
            $sonotime = Sonogram::find($id);
            $sonotime->delete();

            return sendResponse(true, 200, 'sonogram Deleted Successfully!', [], 200);
        } catch (\Exception $ex) {
            return sendResponse(false, 500, 'Internal Server Error', $ex->getMessage(), 200);
        }
    }

    public function getSonogram(Request $request)
    {
        try {
            $sonotimes = Sonogram::whereIn('status', explode(',', $request->status))->orderBy('id', 'desc')->get();
            return sendResponse(true, 200, 'Sonogram Fetched Successfully!', $sonotimes, 200);
        } catch (\Exception $ex) {
            return sendResponse(false, 500, 'Internal Server Error', $ex->getMessage(), 200);
        }
    }
}
