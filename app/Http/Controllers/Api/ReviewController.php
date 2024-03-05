<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Review;

class ReviewController extends Controller
{
    public function store(Request $request)
    {
        try {
            $review = Review::create($request->all());
            
            return sendResponse(true, 200, 'Review Store Successfully!', $review, 200);
        } catch (\Exception $ex) {
            return sendResponse(false, 500, 'Internal Server Error', $ex->getMessage(), 200);
        }
    }

    public function update(Request $request, $id) {
        try {
            $review = Review::find($id);
            $review->update($request->all());
            
            return sendResponse(true, 200, 'Review Updated Successfully!', $review, 200);
        } catch (\Exception $ex) {
            return sendResponse(false, 500, 'Internal Server Error', $ex->getMessage(), 200);
        };
    }


    public function get() {
        try {
            $review = Review::with('booking')->orderBy('id', 'desc')->get();
            return sendResponse(true, 200, 'Review Fetched Successfully!', $review, 200);
        } catch (\Exception $ex) {
            return sendResponse(false, 500, 'Internal Server Error', $ex->getMessage(), 200);
        }
    }

    public function delete(string $id)
    {
        try {
            $review = Review::find($id);
            $review->delete();

            return sendResponse(true, 200, 'Review Deleted Successfully!', [], 200);
        } catch (\Exception $ex) {
            return sendResponse(false, 500, 'Internal Server Error', $ex->getMessage(), 200);
        }
    }
}