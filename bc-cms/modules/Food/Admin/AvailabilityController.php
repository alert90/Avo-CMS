<?php
namespace Modules\Food\Admin;

use Modules\Booking\Models\Booking;
use Modules\Food\Models\Food;
use Modules\Food\Models\FoodDate;

class AvailabilityController extends \Modules\Food\Controllers\AvailabilityController
{
    protected $foodClass;
    protected $foodDateClass;
    protected $bookingClass;
    protected $indexView = 'Food::admin.availability';

    public function __construct(Food $foodClass, FoodDate $foodDateClass,Booking $bookingClass)
    {
        $this->setActiveMenu(route('food.admin.index'));
        $this->middleware('dashboard');
        $this->foodDateClass = $foodDateClass;
        $this->bookingClass = $bookingClass;
        $this->foodClass = $foodClass;
    }

}
