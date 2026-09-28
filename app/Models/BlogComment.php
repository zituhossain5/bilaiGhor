<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A visitor comment on a blog post. New comments start as `pending` and are only shown once an
 * admin approves them. Admin replies are one level deep (parent_id) and flagged `is_admin`.
 */
class BlogComment extends Model
{
    public const PENDING = 'pending';
    public const APPROVED = 'approved';
    public const SPAM = 'spam';

    public const STATUSES = [self::PENDING, self::APPROVED, self::SPAM];

    public const MAX_LENGTH = 2000;

    protected $fillable = [
        'blog_id',
        'parent_id',
        'customer_id',
        'user_id',
        'name',
        'email',
        'body',
        'status',
        'is_admin',
        'ip_address',
        'user_agent',
        'approved_at',
    ];

    protected $casts = [
        'is_admin'    => 'boolean',
        'approved_at' => 'datetime',
    ];

    public function blog(): BelongsTo
    {
        return $this->belongsTo(Blog::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', self::APPROVED);
    }

    public function scopeTopLevel(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }

    /**
     * The comment body as safe HTML: everything escaped, line breaks kept, and bare http(s)
     * links turned into rel="nofollow ugc" anchors (built from already-escaped text).
     */
    public function getBodyHtmlAttribute(): string
    {
        $escaped = e($this->body);

        $linked = preg_replace_callback(
            '~\bhttps?://[^\s<>"\']+~i',
            function (array $m) {
                // Trailing punctuation usually belongs to the sentence, not the URL.
                $url = rtrim($m[0], '.,;:!?)');
                $tail = substr($m[0], strlen($url));

                return '<a href="' . $url . '" rel="nofollow ugc noopener" target="_blank">' . $url . '</a>' . $tail;
            },
            $escaped
        );

        return nl2br($linked, false);
    }
}
