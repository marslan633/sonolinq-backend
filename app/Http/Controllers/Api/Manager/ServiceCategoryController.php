<?php

namespace App\Http\Controllers\Api\Manager;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\ServiceCategory;
use App\Http\Requests\Api\Client\StoreCategoryRequest;
use App\Http\Requests\Api\Client\UpdateCategoryRequest;
use App\Events\CategoryDeleted;

class ServiceCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $categories = ServiceCategory::where('user_id', auth()->user()->id)->whereIn('status', explode(',', $request->status))->orderBy('id', 'desc')->get();
            return sendResponse(true, 200, 'Service Categories Fetched Successfully!', $categories, 200);
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
    public function store(StoreCategoryRequest $request)
    {
        try {
            $id =  Auth::guard('user-api')->user()->id;
            $category = new ServiceCategory();
            $category->user_id = $id;
            $category->name = $request->name;
            $category->status = true;
            $category->save();
            
            /*Retruing Response*/   
            return sendResponse(true, 200, 'Service Category Created Successfully!', $category, 200);
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
            $table = ServiceCategory::find($id);
            return sendResponse(true, 200, 'Service Category Fetched Successfully!', $table, 200);
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
    public function update(UpdateCategoryRequest $request, string $id)
    {
        try {
            $category = ServiceCategory::find($id);
            $category->update($request->all());
            
            return sendResponse(true, 200, 'Service Category Updated Successfully!', $category, 200);
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
            $category = ServiceCategory::find($id);
            $category->delete();
            event(new CategoryDeleted($category));
            return sendResponse(true, 200, 'Service Category Deleted Successfully!', [], 200);
        } catch (\Exception $ex) {
            return sendResponse(false, 500, 'Internal Server Error', $ex->getMessage(), 200);
        }
    }

}