<?php

namespace  Modules\Food;

use Modules\Core\Abstracts\BaseSettingsClass;
use Modules\Core\Models\Settings;

class SettingClass extends BaseSettingsClass
{
    public static function getSettingPages()
    {
        return [
            [
                'id'   => 'food',
                'title' => __("Food Settings"),
                'position'=>22,
                'view'=>"Food::admin.settings.food",
                "keys"=>[
                    'food_disable',
                    'food_page_search_title',
                    'food_page_search_banner',
                    'food_layout_search',
                    'food_location_search_style',
                    'food_page_limit_item',

                    'food_enable_review',
                    'food_review_approved',
                    'food_enable_review_after_booking',
                    'food_review_number_per_page',
                    'food_review_stats',

                    'food_page_list_seo_title',
                    'food_page_list_seo_desc',
                    'food_page_list_seo_image',
                    'food_page_list_seo_share',

                    'food_booking_buyer_fees',
                    'food_vendor_create_service_must_approved_by_admin',
                    'food_allow_vendor_can_change_their_booking_status',
                    'food_allow_vendor_can_change_paid_amount',
                    'food_allow_vendor_can_add_service_fee',
                    'food_search_fields',
                    'food_map_search_fields',

                    'food_allow_review_after_making_completed_booking',
                    'food_deposit_enable',
                    'food_deposit_type',
                    'food_deposit_amount',
                    'food_deposit_fomular',

                    'food_layout_map_option',

                    'food_booking_type',
                    'food_icon_marker_map',

                    'food_map_lat_default',
                    'food_map_lng_default',
                    'food_map_zoom_default',

                    'food_location_search_value',
                    'food_location_search_style',
                    'food_location_radius_value',
                    'food_location_radius_type',
                ],
                'html_keys'=>[

                ]
            ]
        ];
    }
}
