<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuizCompletion extends Model
{
    public $timestamps = false;

    protected $fillable = ['user_id', 'slug', 'completed_at'];

    protected function casts(): array
    {
        return [
            'completed_at' => 'datetime',
        ];
    }
}
