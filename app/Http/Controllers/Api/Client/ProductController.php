<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\{Product, ProductVariant};
use App\Http\Requests\Api\Client\StoreProductRequest;
use App\Http\Requests\Api\Client\UpdateProductRequest;
use Illuminate\Support\Facades\Auth;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $products = Product::with('category')->with('product_variant')->where('client_id', auth()->user()->id)->whereIn('status', explode(',', $request->status))->orderBy('id', 'desc')->get();
            return sendResponse(true, 200, 'Products Fetched Successfully!', $products, 200);
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
            $product = $request->all();
            $product['client_id'] =  Auth::guard('client-api')->user()->id;
            $product['banner_pic'] = $request->file('banner_pic')->store('productImages', 'public');
            $product['status'] = true;
            $product['category_id'] = $request->category;
            
            $product = Product::create($product);
            $product['sorting_order'] = $product->id;
            $product->save();

            if($request->is_variant) {
                $varientsArray = json_decode($request->variants, true);
                foreach($varientsArray as $variant) {
                    $proVariant = new ProductVariant();
                    $proVariant->variant_id =$variant['variant_id'];
                    $proVariant->product_id = $product->id;
                    $proVariant->price = $variant['price'];
                    $proVariant->unit = $variant['unit'];
                    $proVariant->save();
                }
            }
            
            $objProduct = Product::where('id', $product->id)->with('category')->with('product_variant')->first();
            /*Retruing Response*/   
            return sendResponse(true, 200, 'Product Created Successfully!', $objProduct, 200);
        } catch (\Exception $ex) {
            return sendResponse(false, 500, 'Internal Server Error', $ex->getMessage(), 200);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Product $product)
    {
        try {
            $product = Product::where('id', $product->id)->with('category')->with('product_variant')->first();
            return sendResponse(true, 200, 'Product Fetched Successfully!', $product, 200);
        } catch (\Exception $ex) {
            return sendResponse(false, 500, 'Internal Server Error', $ex->getMessage(), 200);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Product $product)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateProductRequest $request, Product $product)
    {
        try {
            $object = Product::find($product->id);
            $product = $request->all();
            $product['client_id'] =  Auth::guard('client-api')->user()->id;
            if($request->file('banner_pic')) {
                $product['banner_pic'] = $request->file('banner_pic')->store('productImages', 'public'); 
            }
            if($request->category) {
                $product['category_id'] = $request->category;  
            }
            if($request->status) {
                $product['status'] = filter_var($product['status'], FILTER_VALIDATE_BOOLEAN);
            }
            $object->update($product);
            
            if($request->is_variant) {
                ProductVariant::where('product_id', $object->id)->delete();
                $varientsArray = json_decode($request->variants, true);
                foreach($varientsArray as $variant) {
                    $proVariant = new ProductVariant();
                    $proVariant->variant_id =$variant['variant_id'];
                    $proVariant->product_id = $object->id;
                    $proVariant->price = $variant['price'];
                    $proVariant->unit = $variant['unit'];
                    $proVariant->save();
                }
            }
            
            $objProduct = Product::where('id', $object->id)->with('category')->with('product_variant')->first();
            return sendResponse(true, 200, 'Product Updated Successfully!', $objProduct, 200);
        } catch (\Exception $ex) {
            return sendResponse(false, 500, 'Internal Server Error', $ex->getMessage(), 200);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Product $product)
    {
        try {
            Product::destroy($product->id);
            return sendResponse(true, 200, 'Product Deleted Successfully!', [], 200);
        } catch (\Exception $ex) {
            return sendResponse(false, 500, 'Internal Server Error', $ex->getMessage(), 200);
        }
    }

    public function getMerchantProducts($id) {
         try {
            $categories = Product::with(['category', 'product_variant'])
                ->where('client_id', $id)
                ->whereNotNull('category_id')
                ->where('status', 1)
                ->latest('id')
                ->get();

            return sendResponse(true, 200, 'Category Product Fetched Successfully!', $categories, 200);
        } catch (\Exception $ex) {
            return sendResponse(false, 500, 'Internal Server Error', $ex->getMessage(), 200);
        }
    }
}