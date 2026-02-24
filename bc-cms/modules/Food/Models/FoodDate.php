<?php
namespace Modules\Food\Models;

use App\BaseModel;

class FoodDate extends BaseModel
{
    protected $table = 'bc_food_dates';

    protected $fillable = [
        'target_id',
        'start_date',
        'end_date',
        'ticket_types',
        'active',
        'note_to_customer',
        'note_to_admin',
        'is_instant'
    ];

    protected $casts = [
        'ticket_types'=>'array',
    ];

    public static function getDatesInRanges($start_date,$end_date,$id){
        return static::query()->where([
            ['start_date','>=',$start_date],
            ['end_date','<=',$end_date],
            ['target_id','=',$id],
        ])->take(100)->get();
    }
}
