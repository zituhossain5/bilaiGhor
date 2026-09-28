<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BlogComment;
use App\Models\GeneralSetting;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

/**
 * Admin moderation for blog comments: filter by status, approve / mark as spam / delete, and
 * reply (one level) as the site with an "Admin" label.
 */
class BlogCommentController extends Controller
{
    public function index(Request $request)
    {
        $status = in_array($request->query('status'), [...BlogComment::STATUSES, 'all'], true)
            ? $request->query('status')
            : BlogComment::PENDING;

        $comments = BlogComment::query()
            ->with(['blog:id,title,slug', 'parent:id,name', 'replies' => fn ($q) => $q->where('is_admin', true)->oldest()])
            ->where('is_admin', false)
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $counts = BlogComment::where('is_admin', false)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('backEnd.blog.comments', compact('comments', 'counts', 'status'));
    }

    public function updateStatus(Request $request, BlogComment $comment)
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(BlogComment::STATUSES)],
        ]);

        $comment->update([
            'status'      => $validated['status'],
            'approved_at' => $validated['status'] === BlogComment::APPROVED ? ($comment->approved_at ?? now()) : null,
        ]);

        Toastr::success('Comment marked as ' . $validated['status'] . '.', 'Success');

        return back();
    }

    public function reply(Request $request, BlogComment $comment)
    {
        $validated = $request->validate([
            'body' => 'required|string|min:1|max:' . BlogComment::MAX_LENGTH,
        ], [], ['body' => 'reply']);

        // Replies are one level deep: replying to a reply attaches to its top-level comment.
        $parent = $comment->parent_id ? $comment->parent : $comment;
        $admin = Auth::guard('admin')->user();

        // A reply to a hidden comment would be invisible, so replying also approves the comment.
        if ($parent->status !== BlogComment::APPROVED) {
            $parent->update(['status' => BlogComment::APPROVED, 'approved_at' => now()]);
        }

        BlogComment::create([
            'blog_id'     => $parent->blog_id,
            'parent_id'   => $parent->id,
            'user_id'     => $admin?->id,
            'name'        => GeneralSetting::query()->value('name') ?: 'Bilai Ghor',
            'email'       => $admin?->email ?? 'admin@localhost',
            'body'        => trim($validated['body']),
            'status'      => BlogComment::APPROVED,
            'is_admin'    => true,
            'ip_address'  => $request->ip(),
            'approved_at' => now(),
        ]);

        Toastr::success('Reply published.', 'Success');

        return back();
    }

    public function destroy(BlogComment $comment)
    {
        $comment->delete(); // replies cascade

        Toastr::success('Comment deleted.', 'Success');

        return back();
    }
}
