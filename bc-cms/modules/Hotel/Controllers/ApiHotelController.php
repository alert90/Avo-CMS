<?php

namespace Modules\Hotel\Controllers;

use App\Http\Controllers\Controller;
use Modules\Hotel\Models\Hotel;
use Modules\Hotel\Models\HotelRoom;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ApiHotelController extends Controller
{
    protected $hotelClass;

    public function __construct(Hotel $hotel)
    {
        $this->hotelClass = $hotel;
    }

    /**
     * Get available rooms for a hotel
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getRooms(Request $request, $id)
    {
        $hotel = $this->hotelClass::find($id);

        if (empty($hotel)) {
            return $this->sendError(__("Hotel not found"));
        }

        // Get filters from request
        $filters = [
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date'),
            'adults' => $request->input('adults', 1),
            'children' => $request->input('children', 0)
        ];

        // Validate required fields for availability check
        if ($filters['start_date'] && $filters['end_date']) {
            $validator = Validator::make($filters, [
                'start_date' => 'required|date_format:Y-m-d',
                'end_date' => 'required|date_format:Y-m-d|after:start_date',
                'adults' => 'required|integer|min:1',
                'children' => 'required|integer|min:0'
            ]);

            if ($validator->fails()) {
                return $this->sendError($validator->errors()->all());
            }

            // Get available rooms with pricing
            $rooms = $hotel->getRoomsAvailability($filters);
        } else {
            // Get all rooms without availability check (for initial display)
            $rooms = $hotel->rooms->map(function ($room) {
                $translation = $room->translate();
                return [
                    'id' => $room->id,
                    'title' => $translation->title,
                    'content' => $translation->content,
                    'price' => $room->price,
                    'sale_price' => $room->sale_price,
                    'size' => $room->size,
                    'beds' => $room->beds,
                    'adults' => $room->adults,
                    'children' => $room->children,
                    'number' => $room->number,
                    'min_day_stays' => $room->min_day_stays,
                    'image' => $room->image_id ? get_file_url($room->image_id, 'medium') : '',
                    'gallery' => $room->getGallery(),
                    'status' => $room->status,
                    'price_html' => format_money($room->sale_price && $room->sale_price < $room->price ? $room->sale_price : $room->price),
                    'size_html' => $room->size ? size_unit_format($room->size) : '',
                    'beds_html' => $room->beds ? 'x' . $room->beds : '',
                    'adults_html' => $room->adults ? 'x' . $room->adults : '',
                    'children_html' => $room->children ? 'x' . $room->children : '',
                ];
            })->toArray();
        }

        return $this->sendSuccess([
            'hotel_id' => $hotel->id,
            'hotel_title' => $hotel->title,
            'rooms' => $rooms,
            'filters' => $filters,
            'booking_data' => $hotel->getBookingData()
        ]);
    }

    /**
     * Check room availability with detailed pricing
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkRoomAvailability(Request $request, $id)
    {
        $hotel = $this->hotelClass::find($id);

        if (empty($hotel)) {
            return $this->sendError(__("Hotel not found"));
        }

        $rules = [
            'start_date' => 'required|date_format:Y-m-d',
            'end_date' => 'required|date_format:Y-m-d|after:start_date',
            'adults' => 'required|integer|min:1',
            'children' => 'required|integer|min:0',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return $this->sendError($validator->errors()->all());
        }

        $filters = $request->only(['start_date', 'end_date', 'adults', 'children']);

        // Validate hotel booking rules
        $numberDays = abs(strtotime($request->input('end_date')) - strtotime($request->input('start_date'))) / 86400;

        if (!empty($hotel->min_day_stays) && $numberDays < $hotel->min_day_stays) {
            return $this->sendError(__("You must to book a minimum of :number days", ['number' => $hotel->min_day_stays]));
        }

        if (!empty($hotel->min_day_before_booking)) {
            $minday_before = strtotime("today +" . $hotel->min_day_before_booking . " days");
            if (strtotime($request->input('start_date')) < $minday_before) {
                return $this->sendError(__("You must book") . " " . $hotel->min_day_before_booking . " " . __("days in advance"));
            }
        }

        // Get available rooms
        $rooms = $hotel->getRoomsAvailability($filters);

        if (empty($rooms)) {
            return $this->sendSuccess([
                'hotel_id' => $hotel->id,
                'rooms' => [],
                'message' => __("No rooms available for the selected dates"),
                'filters' => $filters,
                'booking_data' => $hotel->getBookingData()
            ]);
        }

        return $this->sendSuccess([
            'hotel_id' => $hotel->id,
            'rooms' => $rooms,
            'filters' => $filters,
            'booking_data' => $hotel->getBookingData(),
            'total_nights' => $numberDays
        ]);
    }

    /**
     * Send success response
     */
    private function sendSuccess($data = [], $message = '')
    {
        return response()->json([
            'status' => 1,
            'message' => $message ?: __('Success'),
            'data' => $data
        ]);
    }

    /**
     * Send error response
     */
    private function sendError($message, $data = [])
    {
        return response()->json([
            'status' => 0,
            'message' => $message,
            'data' => $data
        ], 400);
    }
}
