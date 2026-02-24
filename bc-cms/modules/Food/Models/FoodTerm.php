<?php
namespace Modules\Food\Models;

use App\BaseModel;

class FoodTerm extends BaseModel
{
    protected $table = 'bc_food_term';
    protected $fillable = [
        'term_id',
        'target_id'
    ];
}
