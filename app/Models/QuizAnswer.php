<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuizAnswer extends Model
{
    public $timestamps = false;

    protected $fillable = ['user_id', 'slug', 'answer', 'picked', 'correct', 'kind', 'answered_at'];

    protected function casts(): array
    {
        return [
            'correct' => 'boolean',
            'answered_at' => 'datetime',
        ];
    }
}
