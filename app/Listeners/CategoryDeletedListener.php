<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Events\CategoryDeleted;
use App\Listeners\SendShipmentNotification;
use App\Models\Service;

class CategoryDeletedListener
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(CategoryDeleted $event): void
    {
        // Get the category that was deleted
        $category = $event->category;

        // Unlink products by setting their category_id to null
        Service::where('category_id', $category->id)->update(['category_id' => null]);
    }
}