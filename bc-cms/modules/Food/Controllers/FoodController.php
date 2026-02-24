<?php
namespace Modules\Food\Controllers;

use App\Http\Controllers\Controller;
use Modules\Food\Models\Food;
use Illuminate\Http\Request;
use Modules\Location\Models\Location;
use Modules\Location\Models\LocationCategory;
use Modules\Review\Models\Review;
use Modules\Core\Models\Attributes;
use DB;

class FoodController extends Controller
{
    protected $foodClass;
    protected $locationClass;
    /**
     * @var string
     */
    private $locationCategoryClass;

    public function __construct(Food $foodClass, Location $locationClass,LocationCategory $locationCategoryClass)
    {
        $this->foodClass = $foodClass;
        $this->locationClass = $locationClass;
        $this->locationCategoryClass = $locationCategoryClass;
    }

    public function callAction($method, $parameters)
    {
        if(!$this->foodClass::isEnable())
        {
            return redirect('/');
        }
        return parent::callAction($method, $parameters);
    }
    public function index(Request $request)
    {
        $layout = setting_item("food_layout_search", 'normal');
        if ($request->query('_layout')) {
            $layout = $request->query('_layout');
        }
        $is_ajax = $request->query('_ajax');
        $for_map = $request->query('_map',$layout === 'map');

        if(!empty($request->query('limit'))){
            $limit = $request->query('limit');
        }else{
            $limit = !empty(setting_item("food_page_limit_item"))? setting_item("food_page_limit_item") : 9;
        }

        $query = $this->foodClass->search($request->input());
        $list = $query->paginate($limit);

        $markers = [];
        if (!empty($list) and $for_map) {
            foreach ($list as $row) {
                $markers[] = [
                    "id"      => $row->id,
                    "title"   => $row->title,
                    "lat"     => (float)$row->map_lat,
                    "lng"     => (float)$row->map_lng,
                    "gallery" => $row->getGallery(true),
                    "infobox" => view('Food::frontend.layouts.search.loop-grid', ['row' => $row,'disable_lazyload'=>1,'wrap_class'=>'infobox-item'])->render(),
                    'marker' => get_file_url(setting_item("food_icon_marker_map"),'full') ?? url('images/icons/png/pin.png'),
                ];
            }
        }
        $data = [
            'rows' => $list,
            'layout'=>$layout
        ];
        if ($is_ajax) {
            return $this->sendSuccess([
                "markers" => $markers,
                'fragments'=>[
                    '.ajax-search-result'=>view('Food::frontend.ajax.search-result'.($for_map ? '-map' : ''), $data)->render(),
                    '.result-count'=>$list->total() ? ($list->total() > 1 ? __(":count foods found",['count'=>$list->total()]) : __(":count food found",['count'=>$list->total()])) : '',
                    '.count-string'=> $list->total() ? __("Showing :from - :to of :total Foods",["from"=>$list->firstItem(),"to"=>$list->lastItem(),"total"=>$list->total()]) : ''
                ]
            ]);
        }
        $data = [
            'rows'               => $list,
            'list_location'      => $this->locationClass::where('status', 'publish')->limit(1000)->with(['translation'])->get()->toTree(),
            'food_min_max_price' => $this->foodClass::getMinMaxPrice(),
            'markers'            => $markers,
            "blank" => setting_item('search_open_tab') == "current_tab" ? 0 : 1 ,
            "seo_meta"           => $this->foodClass::getSeoMetaForPageList()
        ];
        $data['layout'] = $layout;
        $data['attributes'] = Attributes::where('service', 'food')->orderBy("position","desc")->with(['terms'=>function($query){
            $query->withCount('food');
        },'translation'])->get();

        if ($layout == "map") {
            $data['body_class'] = 'has-search-map';
            $data['html_class'] = 'full-page';
            return view('Food::frontend.search-map', $data);
        }
        return view('Food::frontend.search', $data);
    }

    public function detail(Request $request, $slug)
    {
        $row = $this->foodClass::where('slug', $slug)->with(['location','translation','hasWishList'])->first();;
        if ( empty($row) or !$row->hasPermissionDetailView()) {
            return redirect('/');
        }
        $adminbar_buttons = [];

        if(is_admin()){
            $adminbar_buttons[] = ['label' => __('Edit Food'), 'url' => route('food.admin.edit',['id' => $row->id]), 'icon' => 'edit'];
        }
        $translation = $row->translate();
        $food_related = [];
        $location_id = $row->location_id;
        if (!empty($location_id)) {
            $food_related = $this->foodClass::where('location_id', $location_id)->where("status", "publish")->take(4)->whereNotIn('id', [$row->id])->with(['location','translation','hasWishList'])->get();
        }
        $review_list = $row->getReviewList();
        $data = [
            'row'          => $row,
            'translation'       => $translation,
            'food_related' => $food_related,
            'location_category'=>$this->locationCategoryClass::where("status", "publish")->with('location_category_translations')->get(),
            'booking_data' => $row->getBookingData(),
            'review_list'  => $review_list,
            'seo_meta'  => $row->getSeoMetaWithTranslation(app()->getLocale(),$translation),
            'body_class'=>'is_single',
            'breadcrumbs'       => [
                [
                    'name'  => __('Food'),
                    'url'  => route('food.search'),
                ],
            ],
            'adminbar_buttons' => $adminbar_buttons
        ];
        $data['breadcrumbs'] = array_merge($data['breadcrumbs'],$row->locationBreadcrumbs());
        $data['breadcrumbs'][] = [
            'name'  => $translation->title,
            'class' => 'active'
        ];
        $this->setActiveMenu($row);
        return view('Food::frontend.detail', $data);
    }

    public function search(Request $request)
    {
        $query = $this->foodClass->search($request->input());
        $list = $query->paginate($request->input('limit', 9));
        return $this->sendSuccess([
            'data' => $list->map(function($item){
                return $item->dataForApi();
            }),
            'total' => $list->total(),
            'current_page' => $list->currentPage(),
            'per_page' => $list->perPage(),
        ]);
    }

    public function getMinMaxPrice()
    {
        $min_max = $this->foodClass::getMinMaxPrice();
        return $this->sendSuccess([
            'min_price' => $min_max[0],
            'max_price' => $min_max[1],
        ]);
    }

    public function getFiltersSearch()
    {
        $filters = $this->foodClass::getFiltersSearch();
        return $this->sendSuccess($filters);
    }
}
