<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Blog extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'short_description',
        'description',
        'image',
        'image_alt',
        'views',
        'status',
    ];

    public function comments(): HasMany
    {
        return $this->hasMany(BlogComment::class);
    }

    /** Approved comments and replies — what visitors see and what the cards count. */
    public function approvedComments(): HasMany
    {
        return $this->comments()->approved();
    }

    public function slugRedirects(): HasMany
    {
        return $this->hasMany(BlogSlugRedirect::class);
    }

    /** Absolute canonical URL of the post. */
    public function getUrlAttribute(): string
    {
        return route('blog.details', $this->slug);
    }
}
