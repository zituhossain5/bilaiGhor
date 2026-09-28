<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\BlogCommentController;
use App\Http\Controllers\Admin\BlogController as AdminBlogController;
use App\Models\Blog;
use App\Models\BlogComment;
use App\Models\BlogSlugRedirect;
use App\Models\User;
use App\Services\SitemapService;
use App\Support\BlogSlug;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BlogSlugAndCommentsTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        // Slug changes regenerate sitemap.xml; never let tests overwrite the real file.
        $this->app->instance(SitemapService::class, new class extends SitemapService {
            public function generate(?string $path = null): array { return ['path' => '', 'urls' => 0]; }
        });
    }

    // ── Slugs ────────────────────────────────────────────────────────────────

    public function test_slugs_are_sanitized_lowercase_hyphenated_and_capped_on_a_word_boundary(): void
    {
        $this->assertSame('cats-dogs-a-guide', BlogSlug::sanitize('  Cats & Dogs: A Guide!!  '));
        $this->assertSame('already-clean', BlogSlug::sanitize('already--clean--'));

        $long = BlogSlug::sanitize(str_repeat('vaccination schedule ', 10));
        $this->assertLessThanOrEqual(BlogSlug::MAX_LENGTH, strlen($long));
        $this->assertMatchesRegularExpression('/^[a-z0-9]+(-[a-z0-9]+)*$/', $long);
        $this->assertStringEndsWith('schedule', $long); // not cut mid-word

        $bangla = BlogSlug::sanitize('টিকা না দেওয়ায় বিড়াল');
        $this->assertMatchesRegularExpression('/^[a-z0-9]+(-[a-z0-9]+)*$/', $bangla);
        $this->assertStringStartsWith('tika-na', $bangla);
    }

    public function test_unique_appends_a_number_only_on_a_real_collision_including_old_slugs(): void
    {
        $base = 'unique-' . Str::lower(Str::random(8));
        $this->assertSame($base, BlogSlug::unique($base));

        $first = $this->blog(['slug' => $base]);
        $this->assertSame($base . '-2', BlogSlug::unique($base));
        $this->assertSame($base, BlogSlug::unique($base, $first->id)); // its own slug is free

        BlogSlugRedirect::create(['blog_id' => $first->id, 'old_slug' => $base . '-2']);
        $this->assertSame($base . '-3', BlogSlug::unique($base));
    }

    public function test_post_renders_with_canonical_and_absolute_open_graph_tags(): void
    {
        $blog = $this->blog(['image' => 'uploads/blogs/test.webp', 'short_description' => 'A short summary.']);

        $html = $this->get('/blog/' . $blog->slug)->assertOk()->getContent();
        $url = route('blog.details', $blog->slug);

        $this->assertSame(1, substr_count($html, 'rel="canonical"'), 'exactly one canonical tag');
        $this->assertStringContainsString('<link rel="canonical" href="' . $url . '"', $html);
        $this->assertStringContainsString('<meta property="og:url" content="' . $url . '"', $html);
        $this->assertStringContainsString('<meta property="og:title" content="' . e($blog->title) . '"', $html);
        $this->assertStringContainsString('<meta property="og:description" content="A short summary."', $html);
        $this->assertStringContainsString('<meta property="og:image" content="' . url('public/uploads/blogs/test.webp') . '"', $html);
        $this->assertStringContainsString('<meta name="twitter:card" content="summary_large_image"', $html);
        $this->assertStringContainsString('<h1 class="h3 mb-2">', $html);

        // Share links carry the encoded canonical URL.
        $this->assertStringContainsString('https://www.facebook.com/sharer/sharer.php?u=' . rawurlencode($url), $html);
        $this->assertStringContainsString('https://wa.me/?text=' . rawurlencode($blog->title . ' ' . $url), $html);
        $this->assertStringContainsString('rel="noopener noreferrer"', $html);
    }

    public function test_old_slugs_and_legacy_timestamped_urls_301_to_the_canonical_url(): void
    {
        $blog = $this->blog();
        $canonical = route('blog.details', $blog->slug);

        BlogSlugRedirect::create(['blog_id' => $blog->id, 'old_slug' => $blog->slug . '-old']);
        $this->get('/blog/' . $blog->slug . '-old')->assertStatus(301)->assertRedirect($canonical);

        // Links from before slug history existed: any 10-digit timestamp suffix.
        $this->get('/blog/' . $blog->slug . '-1790507754')->assertStatus(301)->assertRedirect($canonical);
        $this->get('/blog/' . $blog->slug . '-1700000000')->assertStatus(301)->assertRedirect($canonical);

        $this->get('/blog/definitely-not-a-post-' . Str::random(6))->assertNotFound();
    }

    public function test_changing_a_slug_in_admin_keeps_every_previous_slug_redirecting(): void
    {
        $this->loginAdmin();
        $blog = $this->blog();
        $first = $blog->slug;

        $this->adminUpdate($blog, ['slug' => 'Second Slug ' . $blog->id]);
        $second = $blog->fresh()->slug;
        $this->assertSame('second-slug-' . $blog->id, $second);

        $this->adminUpdate($blog, ['slug' => 'third-slug-' . $blog->id]);
        $third = $blog->fresh()->slug;

        $this->get('/blog/' . $first)->assertStatus(301)->assertRedirect(route('blog.details', $third));
        $this->get('/blog/' . $second)->assertStatus(301)->assertRedirect(route('blog.details', $third));
        $this->get('/blog/' . $third)->assertOk();

        // Going back to an old slug makes it canonical again (no redirect loop).
        $this->adminUpdate($blog, ['slug' => $first]);
        $this->assertSame($first, $blog->fresh()->slug);
        $this->get('/blog/' . $first)->assertOk();
        $this->get('/blog/' . $third)->assertStatus(301)->assertRedirect(route('blog.details', $first));
    }

    public function test_saving_a_post_without_touching_the_slug_keeps_its_url(): void
    {
        $this->loginAdmin();
        $blog = $this->blog();
        $slug = $blog->slug;

        $this->adminUpdate($blog, ['slug' => '', 'title' => 'A completely new title']);

        $this->assertSame($slug, $blog->fresh()->slug); // used to be re-stamped with time() on every save
        $this->assertSame(0, BlogSlugRedirect::where('blog_id', $blog->id)->count());
    }

    public function test_new_posts_get_a_clean_slug_without_timestamp(): void
    {
        $this->loginAdmin();
        $title = 'Kitten Care Basics ' . Str::random(5);

        app(AdminBlogController::class)->store(Request::create('/', 'POST', [
            'title' => $title, 'slug' => '', 'description' => '<p>Body</p>', 'status' => 1,
        ]));

        $this->assertSame(Str::slug($title), Blog::where('title', $title)->value('slug'));
    }

    // ── Comments ─────────────────────────────────────────────────────────────

    public function test_comment_is_held_for_moderation_and_shown_only_after_approval(): void
    {
        $blog = $this->blog();

        $this->postComment($blog, ['name' => 'Rumi', 'email' => 'rumi@example.test', 'body' => 'Very helpful, thanks!'])
            ->assertRedirect(route('blog.details', $blog->slug) . '#comments');

        $comment = BlogComment::where('blog_id', $blog->id)->sole();
        $this->assertSame(BlogComment::PENDING, $comment->status);

        $page = $this->get('/blog/' . $blog->slug)->getContent();
        $this->assertStringNotContainsString('Very helpful, thanks!', $page);
        $this->assertStringContainsString('0 Comments', $page);

        $this->loginAdmin();
        app(BlogCommentController::class)->updateStatus(Request::create('/', 'POST', ['status' => 'approved']), $comment);

        $page = $this->get('/blog/' . $blog->slug)->getContent();
        $this->assertStringContainsString('Very helpful, thanks!', $page);
        $this->assertStringContainsString('1 Comment', $page);
        $this->assertStringNotContainsString('rumi@example.test', $page); // email never public
    }

    public function test_comment_output_is_escaped_and_links_are_nofollow_ugc(): void
    {
        $blog = $this->blog();
        $body = '<script>alert("x")</script><img src=x onerror=alert(1)> see https://spam.example/buy?a=1&b=2.';

        $this->postComment($blog, ['body' => $body]);
        BlogComment::where('blog_id', $blog->id)->update(['status' => BlogComment::APPROVED]);

        $page = $this->get('/blog/' . $blog->slug)->getContent();
        $this->assertStringNotContainsString('<script>alert("x")</script>', $page);
        $this->assertStringNotContainsString('<img src=x onerror', $page);
        $this->assertStringContainsString('&lt;script&gt;alert(&quot;x&quot;)&lt;/script&gt;', $page);
        $this->assertStringContainsString('<a href="https://spam.example/buy?a=1&amp;b=2" rel="nofollow ugc noopener" target="_blank">', $page);
    }

    public function test_honeypot_and_too_fast_submissions_are_silently_dropped(): void
    {
        $blog = $this->blog();

        $this->postComment($blog, ['website' => 'http://bot.example'])->assertRedirect();
        $this->postComment($blog, ['form_token' => encrypt(now()->timestamp)])->assertRedirect(); // 0s old
        $this->postComment($blog, ['form_token' => 'forged'])->assertRedirect();

        $this->assertSame(0, BlogComment::where('blog_id', $blog->id)->count());
    }

    public function test_invalid_comments_are_rejected_with_errors(): void
    {
        $blog = $this->blog();

        $this->postComment($blog, ['name' => '', 'email' => 'not-an-email', 'body' => str_repeat('a', BlogComment::MAX_LENGTH + 1)])
            ->assertSessionHasErrors(['name', 'email', 'body'], null, 'comment');

        $this->assertSame(0, BlogComment::where('blog_id', $blog->id)->count());
    }

    public function test_comments_are_rate_limited_per_ip(): void
    {
        $blog = $this->blog();
        $ip = '10.' . random_int(0, 255) . '.' . random_int(0, 255) . '.' . random_int(1, 254);

        foreach (range(1, 3) as $i) {
            $this->postComment($blog, ['body' => "Comment number $i"], $ip)->assertRedirect();
        }
        $this->postComment($blog, ['body' => 'One too many'], $ip)->assertStatus(429);

        $this->assertSame(3, BlogComment::where('blog_id', $blog->id)->count());
    }

    public function test_admin_reply_is_nested_labelled_and_approves_the_comment(): void
    {
        $blog = $this->blog();
        $this->postComment($blog, ['body' => 'How often should kittens be vaccinated?']);
        $comment = BlogComment::where('blog_id', $blog->id)->sole();

        $this->loginAdmin();
        app(BlogCommentController::class)->reply(Request::create('/', 'POST', ['body' => 'Every 3-4 weeks until 16 weeks.']), $comment);

        $this->assertSame(BlogComment::APPROVED, $comment->fresh()->status);
        $reply = BlogComment::where('parent_id', $comment->id)->sole();
        $this->assertTrue($reply->is_admin);

        $page = $this->get('/blog/' . $blog->slug)->getContent();
        $this->assertStringContainsString('Every 3-4 weeks until 16 weeks.', $page);
        $this->assertStringContainsString('blog-comment-badge">Admin<', $page);
        $this->assertStringContainsString('2 Comments', $page);
    }

    public function test_cards_show_real_approved_comment_counts_and_views(): void
    {
        $blog = $this->blog(['views' => 77]);
        BlogComment::create(['blog_id' => $blog->id, 'name' => 'A', 'email' => 'a@x.test', 'body' => 'x', 'status' => 'approved']);
        BlogComment::create(['blog_id' => $blog->id, 'name' => 'B', 'email' => 'b@x.test', 'body' => 'y', 'status' => 'pending']);

        $card = view('components.blog-card', ['blog' => Blog::withCount('approvedComments')->find($blog->id)])->render();

        $this->assertMatchesRegularExpression('/fa-eye"><\/i>\s*77/', $card);
        $this->assertMatchesRegularExpression('/fa-comment"><\/i>\s*1</', $card);
    }

    // ── helpers ──────────────────────────────────────────────────────────────

    private function blog(array $overrides = []): Blog
    {
        $title = $overrides['title'] ?? 'Test Post ' . Str::random(8);

        return Blog::create(array_merge([
            'title'             => $title,
            'slug'              => Str::slug($title),
            'short_description' => null,
            'description'       => '<p>Body of ' . e($title) . '</p>',
            'views'             => 0,
            'status'            => 1,
        ], $overrides));
    }

    private function postComment(Blog $blog, array $overrides = [], ?string $ip = null)
    {
        $ip ??= '10.' . random_int(0, 255) . '.' . random_int(0, 255) . '.' . random_int(1, 254);

        return $this->withServerVariables(['REMOTE_ADDR' => $ip])->post('/blog/' . $blog->slug . '/comments', array_merge([
            'name'       => 'Visitor',
            'email'      => 'visitor@example.test',
            'body'       => 'A thoughtful comment.',
            'website'    => '',
            'form_token' => encrypt(now()->timestamp - 10),
        ], $overrides));
    }

    private function adminUpdate(Blog $blog, array $input): void
    {
        $blog = $blog->fresh();
        app(AdminBlogController::class)->update(Request::create('/', 'POST', array_merge([
            'title'       => $blog->title,
            'description' => $blog->description,
            'status'      => 1,
        ], $input)), $blog->id);
    }

    private function loginAdmin(): User
    {
        $admin = User::create([
            'name'     => 'Blog Test Admin',
            'email'    => 'blog-' . Str::uuid() . '@example.test',
            'password' => bcrypt('secret'),
            'status'   => 1,
        ]);
        $admin->assignRole(Role::findOrCreate('Admin', 'admin'));
        Auth::guard('admin')->login($admin);

        return $admin;
    }
}
