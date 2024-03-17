<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Client;
use App\Models\Booking;
use App\Models\LevelSystem;
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

        foreach ($clients as $client) {;
            foreach ($levelSystems as $levelSystem) {
                if ($this->meetsLevelCriteria($client, $levelSystem, $role)) {
                    $client->update(['level_system' => $levelSystem->id]);
                    break; // No need to check further levels once assigned
                }
            }
        }
    }

private function meetsLevelCriteria($client, $levelSystem, $role)
{
    // Check if the client is verified and active
    if (!$client->is_verified || $client->status !== 'Active') {
        return false;
    }

    // Retrieve the field name based on the role
    $field = $role === 'Doctor/Facility' ? 'doctor_id' : 'sonographer_id';
    
    // Check if there are no successful bookings for the client
    if ($this->countSuccessfulBookings($client, $field) === 0) {
        return true; // No successful bookings, assign Level 0
    }

    // Check if the client meets the other criteria based on the level system
    if ($levelSystem->days && $client->created_at->diffInDays(now()) < $levelSystem->days) {
        return false;
    }

    $successfulBookings = $this->countSuccessfulBookings($client, $field);

    if ($levelSystem->appointment && $successfulBookings < $levelSystem->appointment) {
        return false;
    }

    return true;
}

    private function countSuccessfulBookings($client, $field)
    {
        return Booking::where($field, $client->id)
                      ->where('status', 'Completed')
                      ->count();
    }
}