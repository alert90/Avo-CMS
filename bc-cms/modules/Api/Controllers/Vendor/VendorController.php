<?php
namespace Modules\Api\Controllers\Vendor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Booking\Models\Booking;
use Modules\Booking\Models\Enquiry;
use Modules\News\Models\News;
use Validator;

class VendorController extends Controller
{
    protected $bookingClass;
    protected $enquiryClass;

    public function __construct()
    {
        $this->bookingClass = Booking::class;
        $this->enquiryClass = Enquiry::class;
    }

    // ============================================================
    // DASHBOARD
    // ============================================================
    public function dashboard()
    {
        $user = Auth::user();

        $totalBookings = $this->bookingClass::where('vendor_id', $user->id)->count();
        $pendingBookings = $this->bookingClass::where('vendor_id', $user->id)->where('status', 'pending')->count();
        $completedBookings = $this->bookingClass::where('vendor_id', $user->id)->where('status', 'completed')->count();
        $cancelledBookings = $this->bookingClass::where('vendor_id', $user->id)->where('status', 'cancelled')->count();

        $totalEarnings = $this->bookingClass::where('vendor_id', $user->id)
            ->whereIn('status', ['completed', 'paid'])
            ->sum('total');

        $availablePayoutAmount = $user->available_payout_amount ?? 0;

        $recentBookings = $this->bookingClass::where('vendor_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($booking) {
                return [
                    'id' => $booking->id,
                    'code' => $booking->code,
                    'status' => $booking->status,
                    'total' => $booking->total,
                    'start_date' => $booking->start_date,
                    'end_date' => $booking->end_date,
                    'total_guests' => $booking->total_guests,
                    'created_at' => $booking->created_at,
                    'object_model' => $booking->object_model,
                    'object_id' => $booking->object_id,
                    'service' => $booking->service ? ['title' => $booking->service->title ?? ''] : null,
                ];
            });

        return $this->sendSuccess([
            'cards_report' => [
                'total_bookings' => $totalBookings,
                'pending_bookings' => $pendingBookings,
                'completed_bookings' => $completedBookings,
                'cancelled_bookings' => $cancelledBookings,
                'total_earnings' => $totalEarnings,
                'available_payout' => $availablePayoutAmount,
            ],
            'earning_chart_data' => [],
            'page_title' => __('Vendor Dashboard'),
            'total_bookings' => $totalBookings,
            'pending_bookings' => $pendingBookings,
            'completed_bookings' => $completedBookings,
            'cancelled_bookings' => $cancelledBookings,
            'total_earnings' => $totalEarnings,
            'available_payout_amount' => $availablePayoutAmount,
            'recent_bookings' => $recentBookings,
        ]);
    }

    // ============================================================
    // SERVICES
    // ============================================================
    public function services(Request $request)
    {
        $user = Auth::user();
        $type = $request->get('type');
        $serviceTypes = [
            'hotel' => \Modules\Hotel\Models\Hotel::class,
            'tour' => \Modules\Tour\Models\Tour::class,
            'event' => \Modules\Event\Models\Event::class,
            'car' => \Modules\Car\Models\Car::class,
            'space' => \Modules\Space\Models\Space::class,
            'boat' => \Modules\Boat\Models\Boat::class,
            'food' => \Modules\Food\Models\Food::class,
        ];

        $services = [];

        foreach ($serviceTypes as $key => $class) {
            if (empty($type) || $type === $key) {
                if (class_exists($class)) {
                    $items = $class::where('author_id', $user->id)
                        ->with('translation')
                        ->get()
                        ->map(function ($item) use ($key) {
                            $apiData = $item->dataForApi();
                            return array_merge($apiData, [
                                'service_type' => $key,
                                'model' => class_basename($item),
                                'status' => $item->status,
                            ]);
                        });
                    $services = array_merge($services, $items->toArray());
                }
            }
        }

        return $this->sendSuccess([
            'data' => $services,
            'total' => count($services),
        ]);
    }

    public function serviceDetail(Request $request, $type, $id)
    {
        $user = Auth::user();
        $service = $this->getServiceByType($type, $id, $user->id);

        if (empty($service)) {
            return $this->sendError(__('Service not found'));
        }

        return $this->sendSuccess([
            'data' => array_merge($service->dataForApi(true), [
                'service_type' => $type,
                'model' => ucfirst($type),
            ]),
        ]);
    }

    public function createService(Request $request, $type)
    {
        $user = Auth::user();

        if (!$user->role_id || $user->role_id <= 1) {
            return $this->sendError(__('You do not have permission to create services'));
        }

        $serviceClass = $this->getServiceClass($type);

        if (empty($serviceClass)) {
            return $this->sendError(__('Invalid service type'));
        }

        $row = new $serviceClass();
        $row->author_id = $user->id;
        $row->status = "publish";

        if (setting_item($type . "_vendor_create_service_must_approved_by_admin", 0)) {
            $row->status = "pending";
        }

        $dataKeys = $this->getServiceDataKeys($type);
        $row->fillByAttr($dataKeys, $request->input());

        $res = $row->saveOriginOrTranslation($request->input('lang'), true);

        if ($res) {
            if ($request->input('terms')) {
                $this->saveTerms($row, $type, $request);
            }

            return $this->sendSuccess([
                'data' => $row->dataForApi(),
                'message' => __('Service created successfully'),
            ]);
        }

        return $this->sendError(__('Failed to create service'));
    }

    public function updateService(Request $request, $type, $id)
    {
        $user = Auth::user();

        if (!$user->role_id || $user->role_id <= 1) {
            return $this->sendError(__('You do not have permission'));
        }

        $serviceClass = $this->getServiceClass($type);

        if (empty($serviceClass)) {
            return $this->sendError(__('Invalid service type'));
        }

        $row = $serviceClass::find($id);

        if (empty($row) || $row->author_id != $user->id) {
            return $this->sendError(__('Service not found'));
        }

        $dataKeys = $this->getServiceDataKeys($type);
        $row->fillByAttr($dataKeys, $request->input());

        $res = $row->saveOriginOrTranslation($request->input('lang'), true);

        if ($res) {
            if ($request->input('terms')) {
                $this->saveTerms($row, $type, $request);
            }

            return $this->sendSuccess([
                'data' => $row->dataForApi(),
                'message' => __('Service updated successfully'),
            ]);
        }

        return $this->sendError(__('Failed to update service'));
    }

    public function deleteService(Request $request, $type, $id)
    {
        $user = Auth::user();

        if (!$user->role_id || $user->role_id <= 1) {
            return $this->sendError(__('You do not have permission'));
        }

        $serviceClass = $this->getServiceClass($type);

        if (empty($serviceClass)) {
            return $this->sendError(__('Invalid service type'));
        }

        $row = $serviceClass::where('author_id', $user->id)->find($id);

        if (empty($row)) {
            return $this->sendError(__('Service not found'));
        }

        if ($request->query('permanently_delete')) {
            $row->forceDelete();
        } else {
            $row->delete();
        }

        return $this->sendSuccess(['message' => __('Service deleted successfully')]);
    }

    public function bulkEditService(Request $request, $type, $id)
    {
        $user = Auth::user();
        $serviceClass = $this->getServiceClass($type);

        if (empty($serviceClass)) {
            return $this->sendError(__('Invalid service type'));
        }

        $action = $request->input('action');
        if (empty($action)) {
            return $this->sendError(__('Please select an action'));
        }

        $row = $serviceClass::where('author_id', $user->id)->find($id);
        if (empty($row)) {
            return $this->sendError(__('Service not found'));
        }

        switch ($action) {
            case "make-hide":
                $row->status = "draft";
                break;
            case "make-publish":
                $row->status = "publish";
                break;
            case "clone":
                $this->cloneService($row);
                return $this->sendSuccess(['message' => __('Service cloned successfully')]);
        }

        $row->save();

        return $this->sendSuccess([
            'message' => __('Update success'),
            'data' => $row->dataForApi(),
        ]);
    }

    // ============================================================
    // BOOKINGS
    // ============================================================
    public function bookings(Request $request)
    {
        $user = Auth::user();

        $query = $this->bookingClass::where('vendor_id', $user->id)
            ->orderBy('created_at', 'desc');

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }
        if ($type = $request->get('type')) {
            $query->where('object_model', $type);
        }
        if ($from = $request->get('from')) {
            $query->where('created_at', '>=', $from);
        }
        if ($to = $request->get('to')) {
            $query->where('created_at', '<=', $to);
        }

        $bookings = $query->paginate($request->get('per_page', 20));

        return $this->sendSuccess([
            'data' => $bookings->map(function ($booking) {
                try {
                    $service = $booking->service;
                    $customer = $booking->customer;
                } catch (\Exception $e) {
                    $service = null;
                    $customer = null;
                }

                return [
                    'id' => $booking->id,
                    'code' => $booking->code ?? '',
                    'status' => $booking->status ?? 'pending',
                    'total' => $booking->total ?? 0,
                    'start_date' => $booking->start_date,
                    'end_date' => $booking->end_date,
                    'total_guests' => $booking->total_guests ?? 0,
                    'created_at' => $booking->created_at,
                    'object_model' => $booking->object_model,
                    'object_id' => $booking->object_id,
                    'first_name' => $booking->first_name ?? '',
                    'last_name' => $booking->last_name ?? '',
                    'email' => $booking->email ?? '',
                    'phone' => $booking->phone ?? '',
                    'customer_notes' => $booking->customer_notes ?? '',
                    'gateway' => $booking->gateway ?? '',
                    'service' => $service ? [
                        'id' => $service->id ?? 0,
                        'title' => $service->title ?? '',
                    ] : null,
                    'customer' => $customer ? [
                        'id' => $customer->id,
                        'name' => ($customer->first_name ?? '') . ' ' . ($customer->last_name ?? ''),
                        'email' => $customer->email ?? '',
                        'phone' => $customer->phone ?? '',
                    ] : null,
                ];
            }),
            'total' => $bookings->total(),
            'current_page' => $bookings->currentPage(),
            'last_page' => $bookings->lastPage(),
        ]);
    }

    public function bookingDetail(Request $request, $id)
    {
        $user = Auth::user();
        $booking = $this->bookingClass::where('vendor_id', $user->id)->find($id);

        if (empty($booking)) {
            return $this->sendError(__('Booking not found'));
        }

        $service = $booking->service;
        $customer = $booking->customer;

        $extraPrice = [];
        $meta = $booking->getAllMeta();
        if (!empty($meta)) {
            $metaArray = [];
            foreach ($meta as $m) {
                $val = json_decode($m->val, true);
                $metaArray[$m->name] = is_array($val) ? $val : $m->val;
            }
            $extraPrice = $metaArray['extra_price'] ?? [];
            if (is_string($extraPrice)) {
                $extraPrice = json_decode($extraPrice, true) ?? [];
            }
        }

        $bookingExtraPrice = $booking->extra_price;
        if (is_string($bookingExtraPrice)) {
            $bookingExtraPrice = json_decode($bookingExtraPrice, true) ?? [];
        }

        return $this->sendSuccess([
            'data' => [
                'id' => $booking->id,
                'code' => $booking->code ?? '',
                'status' => $booking->status ?? 'pending',
                'total' => $booking->total ?? 0,
                'start_date' => $booking->start_date,
                'end_date' => $booking->end_date,
                'total_guests' => $booking->total_guests ?? 0,
                'created_at' => $booking->created_at,
                'object_model' => $booking->object_model,
                'object_id' => $booking->object_id,
                'first_name' => $booking->first_name ?? ($customer ? $customer->first_name : ''),
                'last_name' => $booking->last_name ?? ($customer ? $customer->last_name : ''),
                'email' => $booking->email ?? ($customer ? $customer->email : ''),
                'phone' => $booking->phone ?? ($customer ? $customer->phone : ''),
                'gateway' => $booking->gateway ?? '',
                'pay_now' => $booking->pay_now ?? '',
                'customer_notes' => $booking->customer_notes ?? '',
                'extra_price' => !empty($bookingExtraPrice) ? $bookingExtraPrice : $extraPrice,
                'buyer_fees' => $booking->buyer_fees ? (is_string($booking->buyer_fees) ? json_decode($booking->buyer_fees, true) : $booking->buyer_fees) : [],
                'total_before_discount' => $booking->total_before_discount ?? '',
                'total_before_fees' => $booking->total_before_fees ?? '',
                'commission' => $booking->commission ?? 0,
                'commission_type' => $booking->commission_type ? (is_string($booking->commission_type) ? json_decode($booking->commission_type, true) : $booking->commission_type) : null,
                'service' => $service ? [
                    'id' => $service->id,
                    'title' => $service->title ?? '',
                    'address' => $service->address ?? '',
                ] : null,
                'customer' => $customer ? [
                    'id' => $customer->id,
                    'name' => ($customer->first_name ?? '') . ' ' . ($customer->last_name ?? ''),
                    'email' => $customer->email ?? '',
                    'phone' => $customer->phone ?? '',
                ] : null,
                'booking_meta' => [
                    'base_price' => $booking->total_before_fees ?? $booking->total ?? 0,
                    'sale_price' => $booking->total ?? 0,
                    'extra_price' => json_encode(!empty($bookingExtraPrice) ? $bookingExtraPrice : $extraPrice),
                    'guests' => $booking->total_guests ?? 0,
                ],
            ],
        ]);
    }

    public function updateBookingStatus(Request $request, $id)
    {
        $user = Auth::user();
        $status = $request->input('status');

        if (empty($status)) {
            return $this->sendError(__('Status is required'));
        }

        $booking = $this->bookingClass::where('vendor_id', $user->id)->find($id);

        if (empty($booking)) {
            return $this->sendError(__('Booking not found'));
        }

        $booking->status = $status;
        $booking->save();

        return $this->sendSuccess([
            'message' => __('Booking status updated'),
            'data' => ['id' => $booking->id, 'code' => $booking->code, 'status' => $booking->status],
        ]);
    }

    // ============================================================
    // ENQUIRIES
    // ============================================================
    public function enquiries(Request $request)
    {
        $user = Auth::user();

        $query = $this->enquiryClass::where('vendor_id', $user->id)
            ->orderBy('created_at', 'desc');

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        $enquiries = $query->paginate($request->get('per_page', 20));

        return $this->sendSuccess([
            'data' => $enquiries->map(function ($enquiry) {
                return [
                    'id' => $enquiry->id,
                    'name' => $enquiry->name ?? '',
                    'email' => $enquiry->email ?? '',
                    'phone' => $enquiry->phone ?? '',
                    'message' => $enquiry->note ?? $enquiry->notes ?? $enquiry->message ?? '',
                    'notes' => $enquiry->note ?? '',
                    'note' => $enquiry->note ?? '',
                    'service_id' => $enquiry->service_id,
                    'service_type' => $enquiry->object_model ?? '',
                    'status' => $enquiry->status ?? 'pending',
                    'created_at' => $enquiry->created_at,
                    'service' => $enquiry->service ? [
                        'id' => $enquiry->service->id,
                        'title' => $enquiry->service->title ?? '',
                    ] : null,
                ];
            }),
            'total' => $enquiries->total(),
            'current_page' => $enquiries->currentPage(),
            'last_page' => $enquiries->lastPage(),
        ]);
    }

    public function replyEnquiry(Request $request, $id)
    {
        $user = Auth::user();
        $content = $request->input('content');

        if (empty($content)) {
            return $this->sendError(__('Content is required'));
        }

        $enquiry = $this->enquiryClass::where('vendor_id', $user->id)->find($id);

        if (empty($enquiry)) {
            return $this->sendError(__('Enquiry not found'));
        }

        $reply = new \Modules\Booking\Models\EnquiryReply();
        $reply->enquiry_id = $enquiry->id;
        $reply->user_id = $user->id;
        $reply->content = $content;
        $reply->save();

        return $this->sendSuccess([
            'message' => __('Reply sent successfully'),
            'data' => $reply,
        ]);
    }

    // ============================================================
    // NEWS
    // ============================================================
    public function news(Request $request)
    {
        $user = Auth::user();
        $news = News::where('author_id', $user->id)
            ->with('translation')
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 20));

        return $this->sendSuccess([
            'data' => $news->map(fn($item) => $item->dataForApi()),
            'total' => $news->total(),
            'current_page' => $news->currentPage(),
            'last_page' => $news->lastPage(),
        ]);
    }

    public function createNews(Request $request)
    {
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'title' => 'required|max:255',
            'content' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }

        $row = new News();
        $row->author_id = $user->id;
        $row->fill($request->all());
        $row->status = $request->input('status', 'publish');

        if ($row->save()) {
            return $this->sendSuccess([
                'data' => $row->dataForApi(),
                'message' => __('News created successfully'),
            ]);
        }

        return $this->sendError(__('Failed to create news'));
    }

    public function updateNews(Request $request, $id)
    {
        $user = Auth::user();
        $row = News::where('author_id', $user->id)->find($id);

        if (empty($row)) {
            return $this->sendError(__('News not found'));
        }

        $validator = Validator::make($request->all(), ['title' => 'required|max:255']);
        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }

        $row->fill($request->all());

        if ($row->save()) {
            return $this->sendSuccess([
                'data' => $row->dataForApi(),
                'message' => __('News updated successfully'),
            ]);
        }

        return $this->sendError(__('Failed to update news'));
    }

    public function deleteNews(Request $request, $id)
    {
        $user = Auth::user();
        $row = News::where('author_id', $user->id)->find($id);

        if (empty($row)) {
            return $this->sendError(__('News not found'));
        }

        $row->delete();
        return $this->sendSuccess(['message' => __('News deleted successfully')]);
    }

    // ============================================================
    // VERIFICATION
    // ============================================================
    public function verification()
    {
        $user = Auth::user();

        return $this->sendSuccess([
            'data' => [
                'verified' => $user->isVerified() ?? false,
                'verification_data' => $user->getMeta('vendor_verification_data', true),
                'verification_status' => $user->getMeta('vendor_verification_status', 'pending'),
            ],
        ]);
    }

    public function submitVerification(Request $request)
    {
        $user = Auth::user();
        $user->addMeta('vendor_verification_data', $request->all());
        $user->addMeta('vendor_verification_status', 'pending');

        return $this->sendSuccess(['message' => __('Verification data submitted successfully')]);
    }

    // ============================================================
    // PAYOUTS
    // ============================================================
    public function payoutMethods()
    {
        $user = Auth::user();
        $methods = json_decode(setting_item('vendor_payout_methods'));

        if (!is_array($methods) || empty($methods)) {
            return $this->sendError(__('No payout methods available'));
        }

        // ✅ Ensure user_methods is always an object
        $userMethods = $user->available_payout_methods;
        if (is_array($userMethods) || empty($userMethods)) {
            $userMethods = new \stdClass();
        }

        return $this->sendSuccess([
            'available_methods' => $methods,
            'user_methods' => $userMethods,
        ]);
    }

    public function setPayoutMethod(Request $request)
    {
        $user = Auth::user();
        $payoutMethod = $request->input('payout_method');
        $accountInfo = $request->input('account_info');

        if (empty($payoutMethod) || empty($accountInfo)) {
            return $this->sendError(__('Payout method and account info are required'));
        }

        $methods = json_decode(setting_item('vendor_payout_methods'));
        $methodExists = false;
        foreach ($methods as $method) {
            if ($method->id === $payoutMethod) { $methodExists = true; break; }
        }

        if (!$methodExists) {
            return $this->sendError(__('Invalid payout method'));
        }

        // ✅ FIX: Get existing methods and force to be an object
        $existingMeta = $user->getMeta('vendor_payout_accounts');
        if (empty($existingMeta)) {
            $userMethods = new \stdClass();
        } else {
            $userMethods = json_decode($existingMeta);
            // If decoding returned an array, convert to object
            if (is_array($userMethods)) {
                $userMethods = (object) $userMethods;
            }
            // If null, initialize as empty object
            if ($userMethods === null) {
                $userMethods = new \stdClass();
            }
        }

        // Set the method account info
        $userMethods->$payoutMethod = (object) $accountInfo;

        // Save as JSON
        $user->addMeta('vendor_payout_accounts', json_encode($userMethods));

        return $this->sendSuccess(['message' => __('Payout method saved successfully')]);
    }

    public function payouts(Request $request)
    {
        $user = Auth::user();

        $payouts = \Modules\Vendor\Models\VendorPayout::where('vendor_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 20));

        return $this->sendSuccess([
            'data' => $payouts->map(fn($p) => [
                'id' => $p->id,
                'amount' => $p->amount,
                'payout_method' => $p->payout_method,
                'status' => $p->status,
                'note_to_admin' => $p->note_to_admin,
                'account_info' => $p->account_info,
                'created_at' => $p->created_at,
                'updated_at' => $p->updated_at,
            ]),
            'total' => $payouts->total(),
            'current_page' => $payouts->currentPage(),
            'last_page' => $payouts->lastPage(),
            'available_amount' => $user->available_payout_amount ?? 0,
        ]);
    }

    public function createPayoutRequest(Request $request)
    {
        $user = Auth::user();

        if (setting_item('disable_payout')) {
            return $this->sendError(__('Payouts are currently disabled'));
        }

        $vendorPayoutMethods = json_decode(setting_item('vendor_payout_methods'));
        if (!is_array($vendorPayoutMethods) || empty($vendorPayoutMethods)) {
            return $this->sendError(__('No payout methods available'));
        }

        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric',
            'payout_method' => 'required',
        ]);
        if ($validator->fails()) return $this->sendError($validator->errors());

        $amount = $request->input('amount');
        $payoutMethod = $request->input('payout_method');
        $userAvailableMethods = $user->available_payout_methods;

        if (empty($userAvailableMethods) || empty($userAvailableMethods->$payoutMethod)) {
            return $this->sendError(__('You need to set up payout method first'));
        }
        if ($user->available_payout_amount < $amount) {
            return $this->sendError(__('You do not have enough balance'));
        }

        $methodDetail = $userAvailableMethods->$payoutMethod;
        if (!empty($methodDetail->min) && $methodDetail->min > $amount) {
            return $this->sendError(__('Minimum payout is :amount', ['amount' => format_money($methodDetail->min)]));
        }

        $payout = new \Modules\Vendor\Models\VendorPayout();
        $payout->payout_method = $payoutMethod;
        $payout->amount = $amount;
        $payout->note_to_admin = $request->input('note_to_admin');
        // ✅ FIX: json_encode the object to string for database storage
        $payout->account_info = !empty($methodDetail->user) ? json_encode($methodDetail->user) : '';
        $payout->vendor_id = $user->id;
        $payout->status = 'initial';

        if ($payout->save()) {
            return $this->sendSuccess([
                'message' => __('Payout request created'),
                'data' => ['id' => $payout->id, 'amount' => $payout->amount, 'status' => $payout->status],
            ]);
        }

        return $this->sendError(__('Failed to create payout request'));
    }

    // ============================================================
    // AVAILABILITY
    // ============================================================
    public function getAvailability(Request $request, $type, $id)
    {
        $user = Auth::user();
        $service = $this->getServiceByType($type, $id, $user->id);

        if (empty($service)) return $this->sendError(__('Service not found'));

        $availabilityData = [];
        switch ($type) {
            case 'hotel':
                $rooms = \Modules\Hotel\Models\HotelRoom::where('hotel_id', $id)->get();
                $availabilityData = $rooms->map(fn($r) => ['id' => $r->id, 'title' => $r->title, 'price' => $r->price]);
                break;
            case 'tour':
                $availabilityData = [
                    'start_date' => $service->start_date, 'end_date' => $service->end_date,
                    'duration' => $service->duration, 'max_people' => $service->max_people, 'min_people' => $service->min_people,
                ];
                break;
            case 'event':
                $availabilityData = ['start_time' => $service->start_time, 'duration' => $service->duration];
                break;
            case 'car':
                $availabilityData = ['min_day_before_booking' => $service->min_day_before_booking, 'min_day_stays' => $service->min_day_stays];
                break;
            default:
                $availabilityData = ['message' => __('Not available')];
        }

        return $this->sendSuccess(['service_id' => $id, 'service_type' => $type, 'availability' => $availabilityData]);
    }

    public function updateAvailability(Request $request, $type, $id)
    {
        $user = Auth::user();
        $service = $this->getServiceByType($type, $id, $user->id);

        if (empty($service)) return $this->sendError(__('Service not found'));

        switch ($type) {
            case 'tour':
                if ($request->has('start_date')) $service->start_date = $request->input('start_date');
                if ($request->has('end_date')) $service->end_date = $request->input('end_date');
                if ($request->has('max_people')) $service->max_people = $request->input('max_people');
                if ($request->has('min_people')) $service->min_people = $request->input('min_people');
                if ($request->has('duration')) $service->duration = $request->input('duration');
                break;
            case 'event':
                if ($request->has('start_time')) $service->start_time = $request->input('start_time');
                if ($request->has('duration')) $service->duration = $request->input('duration');
                break;
            case 'car':
                if ($request->has('min_day_before_booking')) $service->min_day_before_booking = $request->input('min_day_before_booking');
                if ($request->has('min_day_stays')) $service->min_day_stays = $request->input('min_day_stays');
                break;
            default:
                return $this->sendError(__('Not available for this service type'));
        }

        $service->save();

        return $this->sendSuccess(['message' => __('Availability updated'), 'data' => $service->dataForApi()]);
    }

    // ============================================================
    // HELPERS
    // ============================================================
    protected function getServiceClass($type)
    {
        $services = [
            'hotel' => \Modules\Hotel\Models\Hotel::class,
            'tour' => \Modules\Tour\Models\Tour::class,
            'event' => \Modules\Event\Models\Event::class,
            'car' => \Modules\Car\Models\Car::class,
            'space' => \Modules\Space\Models\Space::class,
            'boat' => \Modules\Boat\Models\Boat::class,
            'food' => \Modules\Food\Models\Food::class,
        ];
        return $services[$type] ?? null;
    }

    protected function getServiceByType($type, $id, $authorId)
    {
        $serviceClass = $this->getServiceClass($type);
        if (empty($serviceClass)) return null;
        return $serviceClass::where('author_id', $authorId)->find($id);
    }

    // ============================================================
    // ✅ ONLY THIS METHOD WAS CHANGED
    // ============================================================
    protected function getServiceDataKeys($type)
    {
        // Common keys shared by ALL service types
        $commonKeys = [
            'title', 'content', 'image_id', 'banner_image_id', 'gallery', 'video',
            'is_featured', 'location_id', 'address', 'map_lat', 'map_lng', 'map_zoom',
            'price', 'sale_price',
        ];

        // Extra price keys — only for types whose DB tables have these columns
        $extraPriceKeys = ['enable_extra_price', 'extra_price', 'enable_service_fee', 'service_fee'];

        $specificKeys = [
            // Hotel: bc_hotels has extra_price + service_fee columns (confirmed in $casts)
            'hotel' => array_merge($commonKeys, $extraPriceKeys, [
                'slug', 'policy', 'star_rate', 'check_in_time', 'check_out_time',
                'allow_full_day', 'min_day_before_booking', 'min_day_stays', 'surrounding',
            ]),
            // Space: bc_spaces has extra_price + service_fee columns (confirmed in $casts)
            'space' => array_merge($commonKeys, $extraPriceKeys, [
                'slug', 'max_guests', 'bed', 'bathroom', 'square', 'faqs', 'surrounding',
            ]),
            // Car: bc_cars has extra_price + service_fee columns (confirmed in $casts)
            'car' => array_merge($commonKeys, $extraPriceKeys, [
                'slug', 'number', 'passenger', 'gear', 'baggage', 'door',
                'is_instant', 'faqs', 'default_state', 'min_day_before_booking',
                'min_day_stays', 'ical_import_url',
            ]),
            // Boat: bc_boats has extra_price + service_fee columns (confirmed in $casts)
            'boat' => array_merge($commonKeys, $extraPriceKeys, [
                'slug', 'max_guests', 'cabin', 'length', 'boat_type',
                'skipper', 'optional_services', 'include', 'exclude',
            ]),
            // Event: bc_events has extra_price + service_fee columns (confirmed in $casts)
            'event' => array_merge($commonKeys, $extraPriceKeys, [
                'slug', 'duration', 'start_time', 'ticket_types', 'default_state',
                'is_instant', 'faqs',
            ]),
            // Food: bc_foods has extra_price + service_fee in $fillable (confirmed)
            'food' => array_merge($commonKeys, $extraPriceKeys, [
                'slug', 'duration', 'start_time', 'end_time', 'duration_unit',
                'ticket_types', 'default_state', 'is_instant', 'faqs',
                'number', 'max_guests', 'enable_fixed_date', 'start_date',
                'end_date', 'last_booking_date', 'surrounding',
            ]),
            // Tour: bc_tours does NOT have extra_price/service_fee columns (Tour uses TourMeta)
            'tour' => array_merge($commonKeys, [
                'slug', 'short_desc', 'category_id', 'duration', 'max_people', 'min_people',
                'faqs', 'include', 'exclude', 'itinerary', 'surrounding',
                'min_day_before_booking', 'enable_fixed_date', 'start_date',
                'end_date', 'last_booking_date', 'date_select_type',
            ]),
        ];

        return $specificKeys[$type] ?? $commonKeys;
    }

    protected function saveTerms($row, $type, $request)
    {
        $termClass = $this->getTermClass($type);
        if (empty($termClass)) return;

        $termClass::where('target_id', $row->id)->delete();

        $termIds = $request->input('terms', []);
        foreach ($termIds as $termId) {
            $termClass::firstOrCreate(['term_id' => $termId, 'target_id' => $row->id]);
        }
    }

    protected function getTermClass($type)
    {
        $terms = [
            'hotel' => \Modules\Hotel\Models\HotelTerm::class,
            'tour' => \Modules\Tour\Models\TourTerm::class,
            'event' => \Modules\Event\Models\EventTerm::class,
            'car' => \Modules\Car\Models\CarTerm::class,
            'space' => \Modules\Space\Models\SpaceTerm::class,
            'boat' => \Modules\Boat\Models\BoatTerm::class,
            'food' => \Modules\Food\Models\FoodTerm::class,
        ];
        return $terms[$type] ?? null;
    }

    protected function cloneService($row)
    {
        $clone = $row->replicate();
        $clone->status = 'draft';
        $clone->push();

        if (!empty($row->terms)) {
            foreach ($row->terms as $term) {
                $e = $term->replicate();
                if ($e->push()) $clone->terms()->save($e);
            }
        }
        if (!empty($row->meta)) {
            $e = $row->meta->replicate();
            if ($e->push()) $clone->meta()->save($e);
        }
        if (!empty($row->translations)) {
            foreach ($row->translations as $translation) {
                $e = $translation->replicate();
                $e->origin_id = $clone->id;
                if ($e->push()) $clone->translations()->save($e);
            }
        }
        return $clone;
    }
}
