<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Booking;
use App\Models\NotificationHistory;
use App\Models\EmailTemplate;
use App\Models\Client;
use Illuminate\Support\Facades\Mail;
use App\Mail\DynamicMail;
use Carbon\Carbon;
use App\Traits\NotificationTrait;

class BookingCron extends Command
{
    use NotificationTrait;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'booking:cron';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // $threeDaysBeforeDelivery = now()->subDays(3);
        $threeDaysBeforeDelivery = now()->subMinutes(3);

        $bookings = Booking::where('delivery_date', '<', $threeDaysBeforeDelivery)
                        ->whereNull('complete_date')
                        ->get();
            
        foreach ($bookings as $booking) {
            // update virtual balance of sonographer
            $sonographer = Client::where('id', $booking->sonographer_id)->first();
            $bookingAmount = $booking->reservation['amount'];
            $calBalance = $sonographer->virtual_balance + $bookingAmount;
            $sonographer->virtual_balance = $calBalance;
            $sonographer->update();
            
            $booking->complete_date = now()->format('Y-m-d H:i:s');
            $booking->status = 'Completed';
            $booking->save();


            // Send Booking Completed Email to Sonographer
            if ($booking->status == 'Completed') {
                
                $emailTemplate = EmailTemplate::where('type', 'booking-complete')->first();
                if($emailTemplate) {
                    $sonographerDetails = $booking->load('sonographer');
                    $details = [
                        'subject' => $emailTemplate->subject,
                        'body'=> $emailTemplate->body,
                        'type' => $emailTemplate->type,
                        'full_name' => $sonographerDetails->sonographer['full_name']
                    ];

                    Mail::to($sonographerDetails->sonographer['email'])->send(new DynamicMail($details)); 
                }
                
                /* Send Booking Completed Notification to Sonographer */
                $tokens = [$booking->sonographer['device_token']];
                if($tokens) {
                    $title = "Appointment Completed!";
                    $body = "Your appointment request has been completed by the doctor.";
                    $sonographer_id = $booking->sonographer['id'];
                    $module_id = $booking->id;
                    $module_name = "Booking Completed";
                        
                    $notification = new NotificationHistory();
                    $notification->title = $title;
                    $notification->body = $body;
                    $notification->module_id = $module_id;
                    $notification->module_name = $module_name;
                    $notification->client_id = $sonographer_id;
                    $notification->save();

                    $count = NotificationHistory::where('client_id', $sonographer_id)->where('is_read', false)->count();
                    $this->sendNotification($tokens, $title, $body, $count);
                }
            }
        }

        \Log::info("Bookings processed successfully!");
    }
}