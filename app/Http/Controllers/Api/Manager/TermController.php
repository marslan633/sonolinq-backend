<?php

namespace App\Http\Controllers\Api\Manager;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{Term};
use Illuminate\Support\Facades\Auth;

class TermController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $items = Term::with('children')->whereIn('status', explode(',', $request->status))->orderBy('id', 'desc')->get();
            return sendResponse(true, 200, 'Term Fetched Successfully!', $items, 200);
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
            $id = Auth::guard('user-api')->user()->id;
            
            $term = Term::create([
                'user_id' => $id,
                'heading' => $request->heading,
                'paragraph' => $request->paragraph,
            ]);

            if($request->has('children')) {
                foreach ($request->children as $childData) {
                    $term->children()->create([
                        'title' => $childData['title'],
                        'paragraph' => $childData['paragraph'],
                    ]);
                }
            }
            
            $objTerm = Term::with('children')->where('id', $term->id)->first();
            
            return sendResponse(true, 200, 'Term Created Successfully!', $objTerm, 200);
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
            $item = Term::find($id);
            return sendResponse(true, 200, 'Term Fetched Successfully!', $item->load('children'), 200);
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
    public function update(Request $request, Term $term)
    {
        try {
            $term->update([
                'heading' => $request->heading,
                'paragraph' => $request->paragraph,
                'status' => $request->status,
            ]);

            if($request->has('children')) {
                $term->children()->delete();

                foreach ($request->children as $childData) {
                    $term->children()->create([
                        'title' => $childData['title'],
                        'paragraph' => $childData['paragraph'],
                        'status' => $childData['status'],
                    ]);
                }
            }

            $term->load('children');
            
            return sendResponse(true, 200, 'Term Updated Successfully!', $term, 200);
        } catch (\Exception $ex) {
            return sendResponse(false, 500, 'Internal Server Error', $ex->getMessage(), 200);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Term $term)
    {
        try {
            $term->children()->delete();
            $term->delete();

            return sendResponse(true, 200, 'Term Deleted Successfully!', [], 200);
        } catch (\Exception $ex) {
            return sendResponse(false, 500, 'Internal Server Error', $ex->getMessage(), 200);
        }
    }
}