<?php

namespace App\Http\Controllers\Api\Manager;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{TicketNote, User, NotificationHistory};
use Illuminate\Support\Facades\Auth;
use App\Traits\NotificationTrait;

class TicketNoteController extends Controller
{
    use NotificationTrait;
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $items = TicketNote::with('ticket')->orderBy('id', 'desc')->get();
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
            $ticketNote = new TicketNote();
            $ticketNote->user_id = $id;
            $ticketNote->ticket_id = $request->ticket_id;
            $ticketNote->type = $request->type;
            $ticketNote->note = $request->note;
            $ticketNote->save(); 
            return sendResponse(true, 200, 'Ticket Note Created Successfully!', $ticketNote->load('ticket'), 200);
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
            $ticketNote = TicketNote::find($id);
            return sendResponse(true, 200, 'TicketNote Fetched Successfully!', $ticketNote->load('ticket'), 200);
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
    public function update(Request $request, TicketNote $ticketNote)
    {
        try {
            $ticketNote->update($request->all());

            /* Send Ticket Note Notification to Client from Admin */
            $tokens = [$ticketNote->client['device_token']];
            if($tokens) {
                $title = "Support Ticket Client Response";
                $body = "You have received support ticket from client";
                $client_id = $ticketNote->client['id'];
                $module_id = $ticketNote->id;
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
            
            return sendResponse(true, 200, 'ticketNote Updated Successfully!', $ticketNote->load('ticket'), 200);
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
            $item = TicketNote::find($id);
            $item->delete();

            return sendResponse(true, 200, 'Support Ticket Deleted Successfully!', [], 200);
        } catch (\Exception $ex) {
            return sendResponse(false, 500, 'Internal Server Error', $ex->getMessage(), 200);
        }
    }

    /**
     * Client Scope Functions
    */
    public function storeTicketNote(Request $request)
    {
        try {
            $id = Auth::guard('client-api')->user()->id;
            $ticketNote = new TicketNote();
            $ticketNote->client_id = $id;
            $ticketNote->ticket_id = $request->ticket_id;
            $ticketNote->type = $request->type;
            $ticketNote->note = $request->note;
            $ticketNote->save();

            /* Send Ticket Note Generated Notification to Admins (Users) */
            $users = User::whereNotNull('device_token')->get();
            if(!$users->isEmpty()) {
                foreach($users as $user) {
                    $tokens = [$user->device_token];
                    if($tokens) {
                        $title = "Ticket Note Generated";
                        $body = "you received the ticket note";
                        $user_id = $user->id;
                        $module_id = $ticketNote->id;
                        $module_name = "Ticket Note";

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
 
            return sendResponse(true, 200, 'Ticket Note Created Successfully!', $ticketNote->load('ticket'), 200);
        } catch (\Exception $ex) {
            return sendResponse(false, 500, 'Internal Server Error', $ex->getMessage(), 200);
        }
    }

    public function getTicketNote()
    {
        try {
            $id =  Auth::guard('client-api')->user()->id;
            $items = TicketNote::with('ticket')->where('client_id', $id)->orderBy('id', 'desc')->get();
            return sendResponse(true, 200, 'Support Ticket Fetched Successfully!', $items, 200);
        } catch (\Exception $ex) {
        return sendResponse(false, 500, 'Internal Server Error', $ex->getMessage(), 200);
        }
    }

    public function showTicketNote(string $id)
    {
        try {
            $ticketNote = TicketNote::find($id);
            return sendResponse(true, 200, 'TicketNote Fetched Successfully!', $ticketNote->load('ticket'), 200);
        } catch (\Exception $ex) {
            return sendResponse(false, 500, 'Internal Server Error', $ex->getMessage(), 200);
        }
    }

    public function deleteTicketNote(string $id)
    {
        try {
            $item = TicketNote::find($id);
            $item->delete();

            return sendResponse(true, 200, 'Support Ticket Deleted Successfully!', [], 200);
        } catch (\Exception $ex) {
            return sendResponse(false, 500, 'Internal Server Error', $ex->getMessage(), 200);
        }
    }

    public function updateTicketNote(Request $request, $id)
    {
        try {
            $ticketNote = TicketNote::find($id);
            $ticketNote->update($request->all());

            /* Send Ticket Note Generated Notification to Admins (Users) */
            $users = User::whereNotNull('device_token')->get();
            if(!$users->isEmpty()) {
                foreach($users as $user) {
                    $tokens = [$user->device_token];
                    if($tokens) {
                        $title = "Update: Ticket Note Generated";
                        $body = "you received the update ticket note";
                        $user_id = $user->id;
                        $module_id = $ticketNote->id;
                        $module_name = "Ticket Note";

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
            
            return sendResponse(true, 200, 'Ticket Note Updated Successfully!', $ticketNote->load('ticket'), 200);
        } catch (\Exception $ex) {
            return sendResponse(false, 500, 'Internal Server Error', $ex->getMessage(), 200);
        }
    }
}