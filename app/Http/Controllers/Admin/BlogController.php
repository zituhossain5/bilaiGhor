<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Blog;
use App\Rules\RichTextImagesHaveAlt;
use App\Services\SitemapService;
use App\Support\BlogSlug;
use Illuminate\Http\Request;

class BlogController extends Controller
{
    /**
     * Blog list
     */
    public function index()
    {
        $blogs = Blog::latest()->get();
        return view('backEnd.blog.index', compact('blogs'));
    }

    /**
     * Create blog form
     */
    public function create()
    {
        return view('backEnd.blog.create');
    }

    /**
     * Store new blog
     */
    public function store(Request $request)
    {
        $request->validate([
            'title'             => 'required|string|max:255',
            'slug'              => 'nullable|string|max:191',
            'short_description' => 'nullable|string|max:500',
            'description'       => ['required', new RichTextImagesHaveAlt],
            'image'             => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'image_alt'         => 'nullable|string|max:255',
            'status'            => 'nullable|in:0,1',
        ]);

        $imagePath = null;

        if ($request->hasFile('image')) {
            $image     = $request->file('image');
            $imageName = time().'.'.$image->getClientOriginalExtension();
            $uploadDir = public_path('uploads/blogs');

            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $image->move($uploadDir, $imageName);
            $imagePath = 'uploads/blogs/'.$imageName;
        }

        Blog::create([
            'title'             => $request->title,
            'slug'              => BlogSlug::unique(BlogSlug::sanitize($request->slug ?: $request->title)),
            'short_description' => $request->short_description,
            'description'       => $request->description,
            'image'             => $imagePath,
            'image_alt'         => $request->image_alt,
            'status'            => $request->status ?? 1,
        ]);

        return redirect()
            ->route('admin.blog.index')
            ->with('success', 'Blog created successfully');
    }

    /**
     * Edit blog form
     */
    public function edit($id)
    {
        $blog = Blog::findOrFail($id);
        return view('backEnd.blog.edit', compact('blog'));
    }

    /**
     * Update blog
     */
    public function update(Request $request, $id)
    {
        $blog = Blog::findOrFail($id);

        $request->validate([
            'title'             => 'required|string|max:255',
            'slug'              => 'nullable|string|max:191',
            'short_description' => 'nullable|string|max:500',
            'description'       => ['required', new RichTextImagesHaveAlt],
            'image'             => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'image_alt'         => 'nullable|string|max:255',
            'status'            => 'nullable|in:0,1',
        ]);

        if ($request->hasFile('image')) {

            // delete old image
            if ($blog->image && file_exists(public_path($blog->image))) {
                unlink(public_path($blog->image));
            }

            $image     = $request->file('image');
            $imageName = time().'.'.$image->getClientOriginalExtension();
            $uploadDir = public_path('uploads/blogs');

            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $image->move($uploadDir, $imageName);
            $blog->image = 'uploads/blogs/'.$imageName;
        }

        // The URL only changes when the slug field is edited; the old slug keeps 301-redirecting.
        $requested = BlogSlug::sanitize($request->slug);
        $slugChanged = $requested !== '' && $requested !== $blog->slug;
        if ($slugChanged) {
            BlogSlug::change($blog, BlogSlug::unique($requested, $blog->id));
        }

        $blog->update([
            'title'             => $request->title,
            'short_description' => $request->short_description,
            'description'       => $request->description,
            'image_alt'         => $request->image_alt,
            'status'            => $request->status ?? 1,
        ]);

        // Keep sitemap.xml on the canonical URL (the old one now 301s). Never block the save.
        if ($slugChanged) {
            rescue(fn () => app(SitemapService::class)->generate(base_path('sitemap.xml')));
        }

        return redirect()
            ->route('admin.blog.index')
            ->with('success', 'Blog updated successfully');
    }

    /**
     * Live slug preview for the create/edit form: the exact slug that saving would produce.
     */
    public function slugPreview(Request $request)
    {
        $request->validate([
            'slug'  => 'nullable|string|max:191',
            'title' => 'nullable|string|max:255',
            'id'    => 'nullable|integer',
        ]);

        $base = BlogSlug::sanitize($request->slug ?: $request->title);
        $slug = $base === '' ? '' : BlogSlug::unique($base, $request->integer('id') ?: null);

        return response()->json([
            'slug'      => $slug,
            'url'       => $slug === '' ? '' : route('blog.details', $slug),
            'collision' => $slug !== '' && $slug !== $base,
        ]);
    }

    /**
     * Delete blog
     */
    public function delete($id)
    {
        $blog = Blog::findOrFail($id);

        if ($blog->image && file_exists(public_path($blog->image))) {
            unlink(public_path($blog->image));
        }

        $blog->delete();

        return back()->with('success', 'Blog deleted successfully');
    }
}
