<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{Review, NotificationHistory};
use App\Traits\NotificationTrait;

class ReviewController extends Controller
{
    use NotificationTrait;
    
    public function store(Request $request)
    {
        try {
            $review = Review::create($request->all());
           
            if($request->rating_doctor) {
                /* Send Notification review to sonographer */
                $booking = $review->booking;
                $tokens = [$booking->sonographer['device_token']];
                if($tokens) {
                        $title = "Notification Review to Sonographer";
                        $body = "Please review the notification sent to you.";
                        $client_id = $booking->sonographer['id'];
                        $module_id = $booking->id;
                        $module_name = "Review";

                        $notification = new NotificationHistory();
                        $notification->title = $title;
                        $notification->body = $body;
                        $notification->module_id = $module_id;
                        $notification->module_name = $module_name;
                        $notification->client_id = $client_id;
                        $notification->save();

                        $count = NotificationHistory::where('client_id', $booking->sonographer['id'])->where('is_read', false)->count();
                        $this->sendNotification($tokens, $title, $body, $count);
                    }
            }

            if($request->rating_sonographer) {
                /* Send Notification review to doctor */
                $booking = $review->booking;
                $tokens = [$booking->doctor['device_token']];
                if($tokens) {
                        $title = "Notification Review to Doctor";
                        $body = "Please review the notification sent to you.";
                        $client_id = $booking->doctor['id'];
                        $module_id = $booking->id;
                        $module_name = "Review";

                        $notification = new NotificationHistory();
                        $notification->title = $title;
                        $notification->body = $body;
                        $notification->module_id = $module_id;
                        $notification->module_name = $module_name;
                        $notification->client_id = $client_id;
                        $notification->save();

                        $count = NotificationHistory::where('client_id', $booking->sonographer['id'])->where('is_read', false)->count();
                    
                        $this->sendNotification($tokens, $title, $body, $count);
                    }
            }
            return sendResponse(true, 200, 'Review Store Successfully!', $review, 200);
        } catch (\Exception $ex) {
            return sendResponse(false, 500, 'Internal Server Error', $ex->getMessage(), 200);
        }
    }

    public function update(Request $request, $id) {
        try {
            $review = Review::find($id);
            $review->update($request->all());

            if($request->rating_doctor) {
                /* Send Notification review to sonographer */
                $booking = $review->booking;
                $tokens = [$booking->sonographer['device_token']];
                if($tokens) {
                        $title = "Notification Review to Sonographer";
                        $body = "Review Update - Please review the notification sent to you.";
                        $client_id = $booking->sonographer['id'];
                        $module_id = $booking->id;
                        $module_name = "Review";

                        $notification = new NotificationHistory();
                        $notification->title = $title;
                        $notification->body = $body;
                        $notification->module_id = $module_id;
                        $notification->module_name = $module_name;
                        $notification->client_id = $client_id;
                        $notification->save();

                        $count = NotificationHistory::where('client_id', $booking->sonographer['id'])->where('is_read', false)->count();
                        $this->sendNotification($tokens, $title, $body, $count);
                    }
            }

            if($request->rating_sonographer) {
                /* Send Notification review to doctor */
                $booking = $review->booking;
                $tokens = [$booking->doctor['device_token']];
                if($tokens) {
                        $title = "Notification Review to Doctor";
                        $body = "Review Update - Please review the notification sent to you.";
                        $client_id = $booking->doctor['id'];
                        $module_id = $booking->id;
                        $module_name = "Review";

                        $notification = new NotificationHistory();
                        $notification->title = $title;
                        $notification->body = $body;
                        $notification->module_id = $module_id;
                        $notification->module_name = $module_name;
                        $notification->client_id = $client_id;
                        $notification->save();

                        $count = NotificationHistory::where('client_id', $booking->sonographer['id'])->where('is_read', false)->count();
                        $this->sendNotification($tokens, $title, $body, $count);
                    }
            }
            
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