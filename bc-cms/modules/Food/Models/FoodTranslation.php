<?php

namespace Modules\Food\Models;

use App\BaseModel;

class FoodTranslation extends Food
{
    protected $table = 'bc_food_translations';

    protected $fillable = [
        'title',
        'content',
        'faqs',
        'address'
    ];

    protected $slugField     = false;
    protected $seo_type = 'food_translation';

    protected $cleanFields = [
        'content'
    ];
    protected $casts = [
        'faqs'  => 'array',
    ];

    public function getSeoType(){
        return $this->seo_type;
    }
    public function getRecordRoot(){
        return $this->belongsTo(Food::class,'origin_id');

    }
    public static function boot() {
		parent::boot();
		static::saving(function($table)  {
			unset($table->extra_price);
			unset($table->price);
			unset($table->sale_price);
		});
	}
}
