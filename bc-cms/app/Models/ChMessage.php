<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChMessage extends Model
{
    public $incrementing = false;
    protected $table = 'ch_messages';

    protected $fillable = [
        'id',
        'from_id',
        'to_id',
        'body',
        'attachment',
        'seen',
        'created_at',
        'updated_at'
    ];

    public function fromUser()
    {
        return $this->belongsTo(User::class, 'from_id');
    }

    public function toUser()
    {
        return $this->belongsTo(User::class, 'to_id');
    }

    protected $with = ['fromUser', 'toUser'];
}
