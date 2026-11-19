<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupportReply extends Model
{
    protected $fillable = ['support_message_id', 'user_id', 'from_staff', 'body'];

    protected function casts(): array
    {
        return [
            'from_staff' => 'boolean',
        ];
    }

    public function supportMessage(): BelongsTo
    {
        return $this->belongsTo(SupportMessage::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return array{id: int, from_staff: bool, author: ?string, body: string, created_at: ?string}
     */
    public function toThread(): array
    {
        return [
            'id' => $this->id,
            'from_staff' => $this->from_staff,
            'author' => $this->from_staff ? 'KhordQuiz Support' : $this->author?->name,
            'body' => $this->body,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
