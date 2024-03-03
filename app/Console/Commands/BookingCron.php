<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Booking;
use Carbon\Carbon;

class BookingCron extends Command
{
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
        $threeDaysBeforeDelivery = now()->subDays(3);

        $bookings = Booking::where('delivery_date', '<', $threeDaysBeforeDelivery)
                        ->whereNull('complete_date')
                        ->get();
            
        foreach ($bookings as $booking) {
            $booking->complete_date = now()->format('Y-m-d H:i:s');
            $booking->status = 'Completed';
            $booking->save();
        }

        \Log::info("Bookings processed successfully!");
    }
}