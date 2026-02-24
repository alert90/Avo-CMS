<?php
namespace Modules\Api\Controllers;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Booking\Models\Service;
use Modules\Flight\Controllers\FlightController;

class SearchController extends Controller
{

    public function search($type = ''){
        $type = $type ? $type : request()->get('type');
        if(empty($type))
        {
            return $this->sendError(__("Type is required"));
        }

        $class = get_bookable_service_by_id($type);
        if(empty($class) or !class_exists($class)){
            return $this->sendError(__("Type does not exists"));
        }

        if(!empty(request()->query('limit'))){
            $limit = request()->query('limit');
        }else{
            $limit = !empty(setting_item($type."_page_limit_item"))? setting_item($type."_page_limit_item") : 9;
        }

        $query = new $class();
        $rows = $query->search(request()->input())->paginate($limit);

        $total = $rows->total();
        return $this->sendSuccess(
            [
                'total'=>$total,
                'total_pages'=>$rows->lastPage(),
                'data'=>$rows->map(function($row) use ($type) {
                    $data = $row->dataForApi();
                    // Add author_id and service type to the response
                    $data['author_id'] = $row->author_id ?? null;
                    $data['service_type'] = $type;
                    return $data;
                }),
            ]
        );
    }


    public function searchServices(Request $request)
    {
        if(!empty($request->query('limit'))){
            $limit = $request->query('limit');
        }else{
            $limit = 9;
        }

        // List all bookable service types
        $serviceTypes = ['hotel', 'tour', 'car', 'boat', 'space', 'flight'];

        $allServices = collect();

        foreach ($serviceTypes as $type) {
            $class = get_bookable_service_by_id($type);
            if(!empty($class) && class_exists($class)) {
                $query = new $class();
                $rows = $query->search($request->input())->get();
                $allServices = $allServices->merge(
                    $rows->map(function($row) use ($type) {
                        $data = $row->dataForApi();
                        // Add author_id and service type to the response
                        $data['author_id'] = $row->author_id ?? null;
                        $data['service_type'] = $type;
                        return $data;
                    })
                );
            }
        }

        // Apply manual pagination on merged results
        $page = $request->query('page', 1);
        $paginated = $allServices->forPage($page, $limit);
        $total = $allServices->count();

        return $this->sendSuccess([
            'total' => $total,
            'total_pages' => ceil($total / $limit),
            'data' => $paginated->values(),
        ]);
    }

    public function getFilters($type = ''){
        $type = $type ? $type : request()->get('type');
        if(empty($type))
        {
            return $this->sendError(__("Type is required"));
        }
        $class = get_bookable_service_by_id($type);
        if(empty($class) or !class_exists($class)){
            return $this->sendError(__("Type does not exists"));
        }
        $data = call_user_func([$class,'getFiltersSearch'],request());
        return $this->sendSuccess(
            [
                'data'=>$data
            ]
        );
    }

    public function getFormSearch($type = ''){
        $type = $type ? $type : request()->get('type');
        if(empty($type))
        {
            return $this->sendError(__("Type is required"));
        }
        $class = get_bookable_service_by_id($type);
        if(empty($class) or !class_exists($class)){
            return $this->sendError(__("Type does not exists"));
        }
        $data = call_user_func([$class,'getFormSearch'],request());
        return $this->sendSuccess(
            [
                'data'=>$data
            ]
        );
    }

    public function detail($type = '',$id = '')
    {
        if(empty($type)){
            return $this->sendError(__("Resource is not available"));
        }
        if(empty($id)){
            return $this->sendError(__("Resource ID is not available"));
        }

        $class = get_bookable_service_by_id($type);
        if(empty($class) or !class_exists($class)){
            return $this->sendError(__("Type does not exists"));
        }

        $row = $class::find($id);
        if(empty($row))
        {
            return $this->sendError(__("Resource not found"));
        }

        // For flight type, use FlightController
        if($type == 'flight'){
            return app()->make(FlightController::class)->getData(\request(),$id);
        }

        try {
            // Get the data using dataForApi method
            $data = $row->dataForApi(true);
            
            // Add author_id and service_type to the response
            if (is_array($data)) {
                $data['author_id'] = $row->author_id ?? null;
                $data['service_type'] = $type;
            } else {
                // If data is an object, convert to array and add fields
                $dataArray = (array) $data;
                $dataArray['author_id'] = $row->author_id ?? null;
                $dataArray['service_type'] = $type;
                $data = $dataArray;
            }

            return $this->sendSuccess([
                'data' => $data
            ]);

        } catch (\Exception $e) {
            \Log::error('API Detail Error: ' . $e->getMessage());
            return $this->sendError(__("Error loading resource details: ") . $e->getMessage());
        }
    }

    public function checkAvailability(Request $request , $type = '',$id = ''){
        if(empty($type)){
            return $this->sendError(__("Resource is not available"));
        }
        if(empty($id)){
            return $this->sendError(__("Resource ID is not available"));
        }
        $class = get_bookable_service_by_id($type);
        if(empty($class) or !class_exists($class)){
            return $this->sendError(__("Type does not exists"));
        }
        $classAvailability = $class::getClassAvailability();
        $classAvailability = app()->make($classAvailability);
        $request->merge(['id' => $id]);
        if($type == "hotel"){
            $request->merge(['hotel_id' => $id]);
            return $classAvailability->checkAvailability($request);
        }
        return $classAvailability->loadDates($request);
    }

    public function checkBoatAvailability(Request $request ,$id = ''){
        if(empty($id)){
            return $this->sendError(__("Boat ID is not available"));
        }
        $class = get_bookable_service_by_id('boat');
        $classAvailability = $class::getClassAvailability();
        $classAvailability = app()->make($classAvailability);
        $request->merge(['id' => $id]);
        return $classAvailability->availabilityBooking($request);
    }
}