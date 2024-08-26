<?php 
namespace App\Traits;

trait BusinessDaysTrait
{
    private function countBusinessDays($startDate, $endDate)
    {
        $count = 0;
        $currentDate = clone $startDate;

        while ($currentDate <= $endDate) {
            if ($currentDate->format("N") < 6) { // Monday to Friday are business days (N: ISO-8601 numeric representation of the day of the week)
                $count++;
            }
            $currentDate->modify('+1 day');
        }

        return $count;
    }
}