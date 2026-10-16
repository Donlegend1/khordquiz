<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuizSession extends Model
{
    public $timestamps = false;

    protected $fillable = ['user_id', 'slug', 'correct', 'total', 'played_at'];

    protected function casts(): array
    {
        return [
            'played_at' => 'datetime',
        ];
    }
}
