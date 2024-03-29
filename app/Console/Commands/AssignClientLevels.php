<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Client;
use App\Models\Booking;
use App\Models\LevelSystem;
use App\Models\Review;
use App\Models\EmailTemplate;
use App\Models\NotificationHistory;
use Illuminate\Support\Facades\Mail;
use App\Mail\DynamicMail;
use Carbon\Carbon;
use App\Traits\NotificationTrait;

class AssignClientLevels extends Command
{
    use NotificationTrait;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clients:assign-levels';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Assign levels to clients based on specified conditions';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->assignLevelsForRole('Doctor/Facility');
        $this->assignLevelsForRole('Sonographer');

        $this->info('Client levels assigned successfully.');
    }

    private function assignLevelsForRole($role)
    {
        $levelSystems = LevelSystem::all();

        $clients = Client::where('role', $role)
                        ->where('is_verified', true)
                        ->where('status', 'Active')
                        ->get();

        foreach ($clients as $client) {
            $assignedLevel = null;

            // Check Previous Level
            $prevLevel = $client->load('level')->level['level'];
            
            foreach ($levelSystems as $levelSystem) {
                if ($this->meetsLevelCriteria($client, $levelSystem, $role)) {
                    $assignedLevel = $levelSystem->level;
                    $client->update(['level_system' => $levelSystem->id]);
                    break; // No need to check further levels once assigned
                }
            }

            // If Level 1 is assigned, check for Level 2
            if ($assignedLevel === 'Level 1') {
                $level2System = LevelSystem::where('level', 'Level 2')->first();
                if ($this->meetsLevelCriteria($client, $level2System, $role)) {
                    $client->update(['level_system' => $level2System->id]);
                }
            }

            // If no level system matched, apply Level 0
            if ($assignedLevel === null) {
                $level0System = LevelSystem::where('level', 'Level 0')->first();
                $client->update(['level_system' => $level0System->id]);
            }

            // After all level systems have been checked and level assigned, compare previous and new levels
            // Check Latest Level
            $newLevel = $client->load('level')->level['level'];
            // Check if level upgraded or downgraded
            if ($newLevel > $prevLevel) {
                // Send email for level upgrade
                $upgradeEmail = EmailTemplate::where('type', 'level-upgrade')->first();

                $emailSubject = str_replace('{{level}}', $newLevel, $upgradeEmail->subject);
                if($upgradeEmail){
                    $details = [
                        'subject' => $emailSubject,
                        'body'=> $upgradeEmail->body,
                        'type' => $upgradeEmail->type,
                        'full_name' => $client->full_name,
                        'level' => $newLevel
                    ];
                     Mail::to($client->email)->send(new DynamicMail($details));
                }
                /* Send Level Upgrade Notification to Client */
                $tokens = [$client->device_token];
                if($tokens) {
                    $title = "Congratulations! You've Reached";
                    $body = "Congratulations! You've Reached";
                    $client_id = $client->id;
                    $module_id = $client->level_system;
                    $module_name = "Level Upgrade";
                            
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
            } elseif ($newLevel < $prevLevel) {
                $downgradeEmail = EmailTemplate::where('type', 'level-downgrade')->first();

                $emailSubject = str_replace('{{level}}', $newLevel, $downgradeEmail->subject);
                if($downgradeEmail){
                    $details = [
                        'subject' => $emailSubject,
                        'body'=> $downgradeEmail->body,
                        'type' => $downgradeEmail->type,
                        'full_name' => $client->full_name,
                        'latest_level' => $newLevel,
                        'previous_level' => $prevLevel
                    ];
                    Mail::to($client->email)->send(new DynamicMail($details));
                }

                /* Send Level Downgrade Notification to Client */
                $tokens = [$client->device_token];
                if($tokens) {
                    $title = "Important: Your Account Level is Downgraded";
                    $body = "Important: Your account level is downgraded!";
                    $client_id = $client->id;
                    $module_id = $client->level_system;
                    $module_name = "Level Downgrade";
                            
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
            }
        }
    }

    private function meetsLevelCriteria($client, $levelSystem, $role)
    {
        if (!$client->is_verified || $client->status !== 'Active') {
            return false;
        }

        $field = $role === 'Doctor/Facility' ? 'doctor_id' : 'sonographer_id';

        $successfulBookings = $this->countSuccessfulBookings($client, $field);
        $rating = $this->calculateRating($client, $field);

        if ($successfulBookings === 0 || ($levelSystem->days != 0 && $client->created_at->diffInDays(now()) >= $levelSystem->days)) {
            if ($rating >= $levelSystem->rating && $successfulBookings >= $levelSystem->appointment) {
                return true;
            }
        }
        return false;
    }


    private function calculateRating($client, $field)
    {
        $reviews = Review::whereHas('booking', function ($query) use ($client, $field) {
            $query->where($field, $client->id);
        })->get();

        $totalRating = 0;
        $totalReviews = $reviews->count();

        foreach ($reviews as $review) {
            if ($field === 'doctor_id') {
                $totalRating += $review->rating_sonographer;
                
            } else {
                $totalRating += $review->rating_doctor;
            }
        }

        // Prevent division by zero
        $rating = $totalReviews > 0 ? ($totalRating / ($totalReviews * 5)) * 100 : 0;

        return $rating;
    }

    private function countSuccessfulBookings($client, $field)
    {
        return Booking::where($field, $client->id)
                    ->where('status', 'Completed')
                    ->count();
    }
}