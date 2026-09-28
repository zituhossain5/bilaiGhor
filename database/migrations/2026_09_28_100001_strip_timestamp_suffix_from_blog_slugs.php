<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Old slugs were "{Str::slug(title)}-{time()}". Drop the timestamp suffix (adding -2, -3 only on a
 * real collision) and keep every old slug as a 301 redirect so existing links never 404.
 * Only the suffix is removed — rewriting the words is left to the admin slug field.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function () {
            $blogs = DB::table('blogs')->orderBy('id')->get(['id', 'slug']);

            foreach ($blogs as $blog) {
                if (!is_string($blog->slug) || !preg_match('/-\d{10}$/', $blog->slug)) {
                    continue;
                }

                $base = preg_replace('/-\d{10}$/', '', $blog->slug);
                $base = $base !== '' ? $base : 'post';
                $slug = $base;
                for ($n = 2; $this->taken($slug, $blog->id); $n++) {
                    $slug = $base . '-' . $n;
                }

                DB::table('blog_slug_redirects')->insertOrIgnore([
                    'blog_id'    => $blog->id,
                    'old_slug'   => $blog->slug,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                // Leave updated_at alone so the sitemap's lastmod doesn't claim a content change.
                DB::table('blogs')->where('id', $blog->id)->update(['slug' => $slug]);
            }
        });
    }

    public function down(): void
    {
        // Restore the timestamped slugs this migration replaced.
        $rows = DB::table('blog_slug_redirects')->get(['blog_id', 'old_slug']);
        foreach ($rows as $row) {
            if (preg_match('/-\d{10}$/', $row->old_slug)) {
                DB::table('blogs')->where('id', $row->blog_id)->update(['slug' => $row->old_slug]);
                DB::table('blog_slug_redirects')->where('old_slug', $row->old_slug)->delete();
            }
        }
    }

    private function taken(string $slug, int $blogId): bool
    {
        return DB::table('blogs')->where('slug', $slug)->where('id', '!=', $blogId)->exists()
            || DB::table('blog_slug_redirects')->where('old_slug', $slug)->where('blog_id', '!=', $blogId)->exists();
    }
};
