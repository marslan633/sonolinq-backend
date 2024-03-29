<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\{NotificationHistory};

class NotificationController extends Controller
{
    /**
     * Client Notifications API
     */
    public function getNotifications(Request $request) 
    {
        try {
            $id =  Auth::guard('client-api')->user()->id;
            $notifications = NotificationHistory::where('client_id', $id)
                ->whereIn('is_read', explode(',', $request->is_read))
                ->orderBy('id', 'desc')
                ->get();

            return sendResponse(true, 200, 'Notifications Fetched Successfully!', $notifications, 200);
        } catch (\Exception $ex) {
            return sendResponse(false, 500, 'Internal Server Error', $ex->getMessage(), 200);
        }
    }

    public function getLatestUnreadNotifications() 
    {
        try {
            $id =  Auth::guard('client-api')->user()->id;
            $notifications = NotificationHistory::where('client_id', $id)
                ->where('is_read', false)
                ->orderBy('id', 'desc')
                ->take(10)
                ->get();

            return sendResponse(true, 200, 'Unread Notifications Fetched Successfully!', $notifications, 200);
        } catch (\Exception $ex) {
            return sendResponse(false, 500, 'Internal Server Error', $ex->getMessage(), 200);
        }
    }

    public function readNotifications(Request $request) {
        try {
            $id =  Auth::guard('client-api')->user()->id;
            $type = $request->input('type');
            // Handle "all" type
            if ($type === "all") {
                $notifications = NotificationHistory::where('client_id', $id)
                    ->where('is_read', false)
                    ->update(['is_read' => true]);
                return sendResponse(true, 200, 'All notifications marked as read.', [], 200);
            }

            // Handle "single" type
            if ($type === "single") {
                $notificationId = $request->input('notification_id');
                $notification = NotificationHistory::find($notificationId);
                if (!$notification) {
                    return sendResponse(true, 200, 'Notification not found.', [], 200);
                }
                $notification->is_read = true;
                $notification->save();
                return sendResponse(true, 200, 'Notification marked as read.', $notification, 200);
            }
        } catch (\Exception $ex) {
            return sendResponse(false, 500, 'Internal Server Error', $ex->getMessage(), 200);
        }
    }


    /**
     * Manager Notifications API
     */
    public function notifications(Request $request) 
    {
        try {
            $id =  Auth::guard('user-api')->user()->id;
            $notifications = NotificationHistory::where('user_id', $id)
                ->whereIn('is_read', explode(',', $request->is_read))
                ->orderBy('id', 'desc')
                ->get();

            return sendResponse(true, 200, 'Notifications Fetched Successfully!', $notifications, 200);
        } catch (\Exception $ex) {
            return sendResponse(false, 500, 'Internal Server Error', $ex->getMessage(), 200);
        }
    }

    public function unreadNotifications() 
    {
        try {
            $id =  Auth::guard('user-api')->user()->id;
            $notifications = NotificationHistory::where('user_id', $id)
                ->where('is_read', false)
                ->orderBy('id', 'desc')
                ->take(10)
                ->get();

            return sendResponse(true, 200, 'Unread Notifications Fetched Successfully!', $notifications, 200);
        } catch (\Exception $ex) {
            return sendResponse(false, 500, 'Internal Server Error', $ex->getMessage(), 200);
        }
    }

    public function readManagerNotifications(Request $request) {
        try {
            $id =  Auth::guard('user-api')->user()->id;
            $type = $request->input('type');
            // Handle "all" type
            if ($type === "all") {
                $notifications = NotificationHistory::where('user_id', $id)
                    ->where('is_read', false)
                    ->update(['is_read' => true]);
                return sendResponse(true, 200, 'All notifications marked as read.', [], 200);
            }

            // Handle "single" type
            if ($type === "single") {
                $notificationId = $request->input('notification_id');
                $notification = NotificationHistory::find($notificationId);
                if (!$notification) {
                    return sendResponse(true, 200, 'Notification not found.', [], 200);
                }
                $notification->is_read = true;
                $notification->save();
                return sendResponse(true, 200, 'Notification marked as read.', $notification, 200);
            }
        } catch (\Exception $ex) {
            return sendResponse(false, 500, 'Internal Server Error', $ex->getMessage(), 200);
        }
    }
}