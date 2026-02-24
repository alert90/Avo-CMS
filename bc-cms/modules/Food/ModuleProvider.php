<?php
namespace Modules\Food;
use Modules\Core\Helpers\SitemapHelper;
use Modules\Food\Models\Food;
use Modules\ModuleServiceProvider;
use Modules\News\Models\News;
use Modules\User\Helpers\PermissionHelper;

class ModuleProvider extends ModuleServiceProvider
{

    public function boot(SitemapHelper $sitemapHelper){

        $this->loadMigrationsFrom(__DIR__ . '/Migrations');

        if(is_installed() and Food::isEnable()){

            $sitemapHelper->add("food",[app()->make(Food::class),'getForSitemap']);
        }
        PermissionHelper::add([
            // Food
            'food_view',
            'food_create',
            'food_update',
            'food_delete',
            'food_manage_others',
            'food_manage_attributes',
        ]);
    }
    /**
     * Register bindings in the container.
     *
     * @return void
     */
    public function register()
    {
        $this->app->register(RouterServiceProvider::class);
    }

    public static function getAdminMenu()
    {
        if(!Food::isEnable()) return [];
        return [
            'food'=>[
                "position"=>51,
                'url'        => route('food.admin.index'),
                'title'      => __('Food'),
                'icon'       => 'ion-ios-restaurant',
                'permission' => 'food_view',
                'group'      => 'catalog',
                'children'   => [
                    'add'=>[
                        'url'        => route('food.admin.index'),
                        'title'      => __('All Foods'),
                        'permission' => 'food_view',
                    ],
                    'create'=>[
                        'url'        => route('food.admin.create'),
                        'title'      => __('Add new Food'),
                        'permission' => 'food_create',
                    ],
                    'attribute'=>[
                        'url'        => route('food.admin.attribute.index'),
                        'title'      => __('Attributes'),
                        'permission' => 'food_manage_attributes',
                    ],
                    'availability'=>[
                        'url'        => route('food.admin.availability.index'),
                        'title'      => __('Availability'),
                        'permission' => 'food_create',
                    ],
                    'recovery'=>[
                        'url'        => route('food.admin.recovery'),
                        'title'      => __('Recovery'),
                        'permission' => 'food_view',
                    ],
                ]
            ]
        ];
    }

    public static function getBookableServices()
    {
        if(!Food::isEnable()) return [];
        return [
            'food'=>Food::class
        ];
    }

    public static function getMenuBuilderTypes()
    {
        if(!Food::isEnable()) return [];
        return [
            'food'=>[
                'class' => Food::class,
                'name'  => __("Food"),
                'items' => Food::searchForMenu(),
                'position'=>51
            ]
        ];
    }

    public static function getUserMenu()
    {
        if(!Food::isEnable()) return [];
        return [
            'food' => [
                'url'   => route('food.vendor.index'),
                'title'      => __("Manage Food"),
                'icon'       => Food::getServiceIconFeatured(),
                'position'   => 81,
                'permission' => 'food_view',
                'children' => [
                    [
                        'url'   => route('food.vendor.index'),
                        'title'  => __("All Foods"),
                    ],
                    [
                        'url'   => route('food.vendor.create'),
                        'title'      => __("Add Food"),
                        'permission' => 'food_create',
                    ],
                    'availability'=>[
                        'url'        => route('food.vendor.availability.index'),
                        'title'      => __('Availability'),
                        'permission' => 'food_create',
                    ],
                    [
                        'url'   => route('food.vendor.recovery'),
                        'title'      => __("Recovery"),
                        'permission' => 'food_create',
                    ],
                ]
            ],
        ];
    }

    public static function getTemplateBlocks(){
        if(!Food::isEnable()) return [];
        return [
            'form_search_food'=>"\\Modules\\Food\\Blocks\\FormSearchFood",
            'list_food'=>"\\Modules\\Food\\Blocks\\ListFood",
            'food_term_featured_box'=>"\\Modules\\Food\\Blocks\\FoodTermFeaturedBox",
        ];
    }
}
