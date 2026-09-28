<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A previous slug of a blog post; /blog/{old_slug} 301-redirects to the post's current URL. */
class BlogSlugRedirect extends Model
{
    protected $fillable = [
        'blog_id',
        'old_slug',
    ];

    public function blog(): BelongsTo
    {
        return $this->belongsTo(Blog::class);
    }
}
