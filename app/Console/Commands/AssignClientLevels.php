<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Client;
use App\Models\Booking;
use App\Models\LevelSystem;
use App\Models\Review;
use Carbon\Carbon;

class AssignClientLevels extends Command
{
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

        if ($successfulBookings === 0 || ($levelSystem->days !== null && $client->created_at->diffInDays(now()) >= $levelSystem->days)) {
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