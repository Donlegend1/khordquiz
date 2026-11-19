<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

/**
 * A member's support request ("ticket"), sent from Support → Email Support on
 * the web or the mobile app's contact screen, plus its reply thread.
 *
 * Status: open (waiting on the team) → answered (team replied, waiting on the
 * member) → resolved. A member reply always puts it back to open.
 */
class SupportMessage extends Model
{
    public const STATUS_OPEN = 'open';

    public const STATUS_ANSWERED = 'answered';

    public const STATUS_RESOLVED = 'resolved';

    public const STATUSES = [self::STATUS_OPEN, self::STATUS_ANSWERED, self::STATUS_RESOLVED];

    protected $fillable = ['user_id', 'topic', 'subject', 'message', 'source', 'attachment_path', 'attachment_name', 'status', 'last_activity_at', 'resolved_at'];

    protected function casts(): array
    {
        return [
            'last_activity_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function replies(): HasMany
    {
        return $this->hasMany(SupportReply::class)->orderBy('id');
    }

    public function title(): string
    {
        return $this->subject ?: $this->topic;
    }

    /**
     * @return array{id: int, topic: string, subject: ?string, title: string, status: string, source: string, preview: string, replies_count: int, created_at: ?string, last_activity_at: ?string}
     */
    public function toSummary(): array
    {
        return [
            'id' => $this->id,
            'topic' => $this->topic,
            'subject' => $this->subject,
            'title' => $this->title(),
            'status' => $this->status,
            'source' => $this->source,
            'preview' => mb_strimwidth($this->message, 0, 140, '…'),
            'replies_count' => (int) ($this->replies_count ?? $this->replies()->count()),
            'created_at' => $this->created_at?->toIso8601String(),
            'last_activity_at' => ($this->last_activity_at ?? $this->created_at)?->toIso8601String(),
        ];
    }

    /**
     * The summary plus the original message, attachment and every reply.
     *
     * @return array<string, mixed>
     */
    public function toThread(): array
    {
        return [
            ...$this->toSummary(),
            'message' => $this->message,
            'attachment' => $this->attachment_path ? [
                'name' => $this->attachment_name ?? basename($this->attachment_path),
                'url' => Storage::disk('public')->url($this->attachment_path),
            ] : null,
            'replies' => $this->replies->map->toThread()->values()->all(),
        ];
    }
}
