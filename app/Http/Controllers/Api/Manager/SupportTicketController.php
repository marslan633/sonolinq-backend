<?php

namespace App\Http\Controllers\Api\Manager;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{SupportTicket, User, NotificationHistory};
use Illuminate\Support\Facades\Auth;
use App\Traits\NotificationTrait;

class SupportTicketController extends Controller
{
    use NotificationTrait;
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $items = SupportTicket::with('booking')->whereIn('status', explode(',', $request->status))->orderBy('id', 'desc')->get();
            return sendResponse(true, 200, 'Support Ticket Fetched Successfully!', $items, 200);
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
            $supportTicket = new SupportTicket();
            $supportTicket->user_id = $id;
            $supportTicket->title = $request->title;
            $supportTicket->type = $request->type;
            $supportTicket->booking_id = $request->booking_id;
            $supportTicket->comment = $request->comment;
            $supportTicket->status = $request->status;
            $supportTicket->save();

            // Generate ticket_tracking_id
            $prefix = 'SPT';
            $randomNumber = mt_rand(1000, 9999);
            $supportTicket->update(['ticket_tracking_id' => $prefix . $randomNumber . $supportTicket->id]);
 
            return sendResponse(true, 200, 'Support Ticket Created Successfully!', $supportTicket->load('booking'), 200);
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
            $supportTicket = SupportTicket::with('ticket_notes')->find($id);
            return sendResponse(true, 200, 'supportTicket Fetched Successfully!', $supportTicket->load('booking'), 200);
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
    public function update(Request $request, SupportTicket $supportTicket)
    {
        try {
            $supportTicket->update($request->all());
            
            /* Send Ticket Support Notification from Admin to Client */
            $tokens = [$supportTicket->client['device_token']];
            if($tokens) {
                $title = "Support Ticket Response";
                $body = "You have received support ticket from admin";
                $client_id = $supportTicket->client['id'];
                $module_id = $supportTicket->id;
                $module_name = "Support Ticket";

                $notification = new NotificationHistory();
                $notification->title = $title;
                $notification->body = $body;
                $notification->module_id = $module_id;
                $notification->module_name = $module_name;
                $notification->client_id = $client_id;
                $notification->save();
                        
                $count = NotificationHistory::where('client_id', $client_id)->where('is_read', false)->count();
                $this->sendNotification($tokens, $title, $body, $count);
            }          
            return sendResponse(true, 200, 'supportTicket Updated Successfully!', $supportTicket->load('booking'), 200);
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
            $item = SupportTicket::find($id);
            $item->delete();

            return sendResponse(true, 200, 'Support Ticket Deleted Successfully!', [], 200);
        } catch (\Exception $ex) {
            return sendResponse(false, 500, 'Internal Server Error', $ex->getMessage(), 200);
        }
    }

    /**
     * Client Scope Functions
    */
    public function storeTicket(Request $request)
    {
        try {
            $id =  Auth::guard('client-api')->user()->id;
            $supportTicket = new SupportTicket();
            $supportTicket->client_id = $id;
            $supportTicket->title = $request->title;
            $supportTicket->type = $request->type;
            $supportTicket->booking_id = $request->booking_id;
            $supportTicket->comment = $request->comment;
            $supportTicket->status = $request->status;
            $supportTicket->save();

            // Generate ticket_tracking_id
            $prefix = 'SPT';
            $randomNumber = mt_rand(1000, 9999);
            $supportTicket->update(['ticket_tracking_id' => $prefix . $randomNumber . $supportTicket->id]);
 
            /* Send Support Ticket Generated Notification to Admins (Users) */
            $users = User::whereNotNull('device_token')->get();
            if(!$users->isEmpty()) {
                foreach($users as $user) {
                    $tokens = [$user->device_token];
                    if($tokens) {
                        $title = "Support Ticket Generated";
                        $body = "you received the support ticket";
                        $user_id = $user->id;
                        $module_id = $supportTicket->id;
                        $module_name = "Support Ticket";

                        $notification = new NotificationHistory();
                        $notification->title = $title;
                        $notification->body = $body;
                        $notification->module_id = $module_id;
                        $notification->module_name = $module_name;
                        $notification->user_id = $user_id;
                        $notification->save();

                        $count = NotificationHistory::where('user_id', $user_id)->where('is_read', false)->count();
                        $this->sendNotification($tokens, $title, $body, $count);
                    }
                } 
            }
            
            return sendResponse(true, 200, 'Support Ticket Created Successfully!', $supportTicket->load('booking'), 200);
        } catch (\Exception $ex) {
            return sendResponse(false, 500, 'Internal Server Error', $ex->getMessage(), 200);
        }
    }


    public function getTicket(Request $request)
    {
        try {
            $id =  Auth::guard('client-api')->user()->id;
            $items = SupportTicket::with('booking')->where('client_id', $id)->whereIn('status', explode(',', $request->status))->orderBy('id', 'desc')->get();
            return sendResponse(true, 200, 'Support Ticket Fetched Successfully!', $items, 200);
        } catch (\Exception $ex) {
        return sendResponse(false, 500, 'Internal Server Error', $ex->getMessage(), 200);
        }
    }

    public function showTicket(string $id)
    {
        try {
            $supportTicket = SupportTicket::with('ticket_notes')->find($id);
            return sendResponse(true, 200, 'supportTicket Fetched Successfully!', $supportTicket->load('booking'), 200);
        } catch (\Exception $ex) {
            return sendResponse(false, 500, 'Internal Server Error', $ex->getMessage(), 200);
        }
    }


    public function deleteTicket(string $id)
    {
        try {
            $item = SupportTicket::find($id);
            $item->delete();

            return sendResponse(true, 200, 'Support Ticket Deleted Successfully!', [], 200);
        } catch (\Exception $ex) {
            return sendResponse(false, 500, 'Internal Server Error', $ex->getMessage(), 200);
        }
    }

    public function updateTicket(Request $request, $id)
    {
        try {
            $supportTicket = SupportTicket::find($id);
            $supportTicket->update($request->all());
            
            return sendResponse(true, 200, 'supportTicket Updated Successfully!', $supportTicket->load('booking'), 200);
        } catch (\Exception $ex) {
            return sendResponse(false, 500, 'Internal Server Error', $ex->getMessage(), 200);
        }
    }
}