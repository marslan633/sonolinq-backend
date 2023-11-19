<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\Api\Client\StoreTableRequest;
use App\Http\Requests\Api\Client\UpdateTableRequest;
use Illuminate\Support\Facades\Auth;

use App\Models\Table;

class TableController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $tables = Table::where('client_id', auth()->user()->id)->whereIn('status', explode(',', $request->status))->orderBy('id', 'desc')->get();
            return sendResponse(true, 200, 'Table Fetched Successfully!', $tables, 200);
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
    public function store(StoreTableRequest $request)
    {
        try {
            $id =  Auth::guard('client-api')->user()->id;
            $noOfTables = $request->input('no_of_tables');

            $totalTables = $totalTables = $request->input('total_tables');
            // Find the last record_no in the table
            $lastRecord = Table::orderBy('id', 'desc')->first();
            
            $lastRecordNo = 0;

            if ($lastRecord) {
                $lastRecordNo = intval(str_replace('Table No #', '', $lastRecord->record_no));
            }

            // Start creating new records
            $newRecords = [];
            for ($i = 1; $i <= $noOfTables; $i++) {
                $recordNo = 'Table No #' . ($lastRecordNo + $i + $totalTables);
                $newRecord = new Table();
                $newRecord->table_no = $recordNo;
                $newRecord->client_id = $id;
                $newRecord->status = true;
                $newRecord->save();
                $newRecords[] = $newRecord;
            }
            
            /*Retruing Response*/   
            return sendResponse(true, 200, 'Table Created Successfully!', $newRecords, 200);
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
            $table = Table::find($id);
            return sendResponse(true, 200, 'Table Fetched Successfully!', $table, 200);
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
    public function update(UpdateTableRequest $request, string $id)
    {
        try {
            $table = Table::find($id);
            $table->update($request->all());
            
            return sendResponse(true, 200, 'Table Updated Successfully!', $table, 200);
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
            Table::destroy($id);
            return sendResponse(true, 200, 'Table Deleted Successfully!', [], 200);
        } catch (\Exception $ex) {
            return sendResponse(false, 500, 'Internal Server Error', $ex->getMessage(), 200);
        }
    }
}