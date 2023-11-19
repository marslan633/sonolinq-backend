<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\ProductCategory;
use App\Http\Requests\Api\Client\StoreCategoryRequest;
use App\Http\Requests\Api\Client\UpdateCategoryRequest;
use App\Events\CategoryDeleted;

class ProductCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $categories = ProductCategory::where('client_id', auth()->user()->id)->whereIn('status', explode(',', $request->status))->orderBy('id', 'desc')->get();
            return sendResponse(true, 200, 'Category Product Fetched Successfully!', $categories, 200);
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
            $id =  Auth::guard('client-api')->user()->id;
            $category = new ProductCategory();
            $category->client_id = $id;
            $category->name = $request->name;
            $category->status = true;
            $category->save();
            $category->sorting_order = $category->id;
            $category->update();
            
            /*Retruing Response*/   
            return sendResponse(true, 200, 'Product Category Created Successfully!', $category, 200);
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
            $table = ProductCategory::find($id);
            return sendResponse(true, 200, 'Category Product Fetched Successfully!', $table, 200);
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
            $category = ProductCategory::find($id);
            $category->update($request->all());
            
            return sendResponse(true, 200, 'Category Product Updated Successfully!', $category, 200);
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
            $category = ProductCategory::find($id);
            $category->delete();
            event(new CategoryDeleted($category));
            return sendResponse(true, 200, 'Category Product Deleted Successfully!', [], 200);
        } catch (\Exception $ex) {
            return sendResponse(false, 500, 'Internal Server Error', $ex->getMessage(), 200);
        }
    }

    public function getMerchantcategories($id) {
         try {
            $categories = ProductCategory::where('client_id', $id)->orderBy('id', 'desc')->get();
            return sendResponse(true, 200, 'Category Product Fetched Successfully!', $categories, 200);
        } catch (\Exception $ex) {
            return sendResponse(false, 500, 'Internal Server Error', $ex->getMessage(), 200);
        }
    }
}