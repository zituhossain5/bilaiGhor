<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Blog;
use App\Models\BlogComment;
use App\Models\BlogSlugRedirect;
use App\Support\BlogSlug;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class BlogController extends Controller
{
    /** Submissions faster than this after the page rendered are treated as bots. */
    private const MIN_FORM_SECONDS = 3;

    /**
     * 🔹 Blog List Page
     * URL: /blogs
     */
    public function index()
    {
        $blogs = Blog::where('status', 1)
            ->withCount('approvedComments')
            ->latest()
            ->paginate(9);

        // ✅ correct blade path
        return view('frontEnd.layouts.pages.blog.index', compact('blogs'));
    }

    /**
     * 🔹 Blog Details Page
     * URL: /blog/{slug}
     */
    public function details($slug)
    {
        $blog = Blog::where('slug', $slug)
            ->where('status', 1)
            ->first();

        if (!$blog) {
            // Old or legacy URL → 301 to the post's current, canonical URL.
            $target = $this->resolveMovedSlug($slug);
            abort_unless($target, 404);

            return redirect()->to($target->url, 301);
        }

        // 👁️ view count increment
        $blog->increment('views');

        // 🔹 Recent Blogs
        $recentBlogs = Blog::where('status', 1)
            ->where('id', '!=', $blog->id)
            ->latest()
            ->limit(5)
            ->get();

        // Approved comments, oldest first so threads read in order; approved replies nested.
        $comments = $blog->comments()
            ->approved()
            ->topLevel()
            ->with(['replies' => fn ($q) => $q->approved()->oldest()])
            ->oldest()
            ->get();
        $commentCount = $blog->approvedComments()->count();

        $customer = Auth::guard('customer')->user();

        $seo = [
            'canonical'   => $blog->url,
            'title'       => $blog->title,
            'description' => Str::limit(trim(preg_replace('/\s+/u', ' ', strip_tags(
                html_entity_decode($blog->short_description ?: $blog->description, ENT_QUOTES | ENT_HTML5, 'UTF-8')
            ))), 160, '…'),
            'image'       => $blog->image ? url('public/' . $blog->image) : null,
            'image_alt'   => $blog->image_alt ?: $blog->title,
        ];

        // ✅ correct blade path
        return view(
            'frontEnd.layouts.pages.blog.details',
            compact('blog', 'recentBlogs', 'comments', 'commentCount', 'customer', 'seo')
        );
    }

    /**
     * Store a visitor comment. It is saved as "pending" and only shown after an admin approves it.
     * Rate-limited per IP by the route (throttle:blog-comments).
     */
    public function storeComment(Request $request, $slug)
    {
        $blog = Blog::where('slug', $slug)->where('status', 1)->first() ?? $this->resolveMovedSlug($slug);
        abort_unless($blog, 404);

        $back = $blog->url . '#comments';
        $thanks = fn () => redirect()->to($back)->with('blog_comment_status', 'pending');

        // Bots: filled honeypot or impossibly fast submit → pretend success, store nothing.
        if ($request->filled('website') || !$this->submittedByHuman($request->input('form_token'))) {
            return $thanks();
        }

        $validated = $request->validateWithBag('comment', [
            'name'  => 'required|string|min:2|max:100',
            'email' => 'required|email:rfc|max:191',
            'body'  => 'required|string|min:2|max:' . BlogComment::MAX_LENGTH,
        ], [], [
            'body' => 'comment',
        ]);

        // Same text from the same IP within 10 minutes: a double submit, keep only one.
        $duplicate = BlogComment::where('blog_id', $blog->id)
            ->where('ip_address', $request->ip())
            ->where('body', $validated['body'])
            ->where('created_at', '>=', now()->subMinutes(10))
            ->exists();

        if (!$duplicate) {
            BlogComment::create([
                'blog_id'     => $blog->id,
                'customer_id' => Auth::guard('customer')->id(),
                'name'        => trim($validated['name']),
                'email'       => strtolower(trim($validated['email'])),
                'body'        => trim($validated['body']),
                'status'      => BlogComment::PENDING,
                'ip_address'  => $request->ip(),
                'user_agent'  => Str::limit((string) $request->userAgent(), 250, ''),
            ]);
        }

        Toastr::success('Thanks! Your comment will appear after it is approved.', 'Comment received');

        return $thanks();
    }

    /**
     * A post that used to live at $slug: a stored previous slug, or a legacy
     * "{words}-{10-digit timestamp}" URL whose words still match the post.
     */
    private function resolveMovedSlug(string $slug): ?Blog
    {
        $redirect = BlogSlugRedirect::where('old_slug', $slug)->first();
        if ($redirect) {
            return Blog::where('id', $redirect->blog_id)->where('status', 1)->first();
        }

        if (!preg_match(BlogSlug::LEGACY_SUFFIX, $slug)) {
            return null;
        }

        // Timestamped links from before slug history existed (every edit used to re-stamp the
        // slug): match on the words, via either the current slug or another stamped old slug.
        $base = preg_replace(BlogSlug::LEGACY_SUFFIX, '', $slug);
        $viaHistory = BlogSlugRedirect::where('old_slug', 'like', $base . '-%')
            ->get()
            ->first(fn ($r) => preg_replace(BlogSlug::LEGACY_SUFFIX, '', $r->old_slug) === $base);

        return Blog::where('status', 1)
            ->where(fn ($q) => $q->where('slug', $base)->when($viaHistory, fn ($q) => $q->orWhere('id', $viaHistory->blog_id)))
            ->orderByRaw('id = ? desc', [$viaHistory?->blog_id ?? 0])
            ->first();
    }

    private function submittedByHuman(?string $token): bool
    {
        try {
            $renderedAt = (int) decrypt((string) $token);
        } catch (DecryptException) {
            return false;
        }

        $age = now()->timestamp - $renderedAt;

        return $age >= self::MIN_FORM_SECONDS && $age <= 86400;
    }
}
