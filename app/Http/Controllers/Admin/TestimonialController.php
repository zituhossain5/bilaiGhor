<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Testimonial;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class TestimonialController extends Controller
{
    /** Max upload size in kilobytes. Change here to adjust the limit everywhere. */
    private const MAX_IMAGE_KB = 2048;

    /** Allowed image extensions. */
    private const ALLOWED_MIMES = 'jpg,jpeg,png,webp';

    /** Upload folder, relative to public/. */
    private const UPLOAD_DIR = 'uploads/testimonials';

    /** Homepage cache key set in FrontendController::index(). */
    private const HOMEPAGE_CACHE_KEY = 'frontend_homepage_v3';

    public function index()
    {
        $testimonials = Testimonial::ordered()->get();
        return view('backEnd.testimonial.index', compact('testimonials'));
    }

    public function create()
    {
        return view('backEnd.testimonial.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'image'      => 'required|array|min:1',
            'image.*'    => 'required|image|mimes:' . self::ALLOWED_MIMES . '|max:' . self::MAX_IMAGE_KB,
            // One alt text per selected file, in the same order (image_alt[0] describes image[0]).
            'image_alt'   => 'required|array|size:' . count((array) $request->file('image')),
            'image_alt.*' => 'required|string|max:255',
            'sort_order' => 'nullable|integer|min:0',
        ], [
            'image.required'       => 'Please select at least one image.',
            'image.*.required'     => 'Please select at least one image.',
            'image.*.max'          => 'Each image must be ' . (self::MAX_IMAGE_KB / 1024) . 'MB or smaller.',
            'image_alt.required'   => 'Please add alt text for each image.',
            'image_alt.size'       => 'Please add alt text for each image.',
            'image_alt.*.required' => 'Please add alt text for each image.',
        ]);

        $sortOrder = (int) ($request->sort_order ?? 0);
        $status    = $request->status ? 1 : 0;
        $count     = 0;

        // Each uploaded file becomes its own testimonial record.
        $alts = array_values((array) $request->input('image_alt', []));

        foreach (array_values($request->file('image')) as $index => $image) {
            Testimonial::create([
                'image'      => $this->storeImage($image),
                'image_alt'  => $alts[$index] ?? null,
                'status'     => $status,
                'sort_order' => $sortOrder + $count,
            ]);

            $count++;
        }

        $this->flushHomepageCache();

        return redirect()->route('admin.testimonial.index')
            ->with('success', $count > 1
                ? $count . ' testimonials created successfully'
                : 'Testimonial created successfully');
    }

    public function edit($id)
    {
        $testimonial = Testimonial::findOrFail($id);
        return view('backEnd.testimonial.edit', compact('testimonial'));
    }

    public function update(Request $request, $id)
    {
        $testimonial = Testimonial::findOrFail($id);

        $request->validate([
            'image'      => 'nullable|image|mimes:' . self::ALLOWED_MIMES . '|max:' . self::MAX_IMAGE_KB,
            'image_alt'  => 'nullable|string|max:255',
            'sort_order' => 'nullable|integer|min:0',
        ], [
            'image.max' => 'The image must be ' . (self::MAX_IMAGE_KB / 1024) . 'MB or smaller.',
        ]);

        if ($request->hasFile('image')) {
            $this->deleteImage($testimonial->image);
            $testimonial->image = $this->storeImage($request->file('image'));
        }

        $testimonial->image_alt  = $request->image_alt;
        $testimonial->status     = $request->status ? 1 : 0;
        $testimonial->sort_order = (int) ($request->sort_order ?? 0);
        $testimonial->save();

        $this->flushHomepageCache();

        return redirect()->route('admin.testimonial.index')
            ->with('success', 'Testimonial updated successfully');
    }

    public function delete($id)
    {
        $testimonial = Testimonial::findOrFail($id);

        $this->deleteImage($testimonial->image);
        $testimonial->delete();

        $this->flushHomepageCache();

        return back()->with('success', 'Testimonial deleted successfully');
    }

    /** The homepage is cached for 5 minutes, so drop it to show changes right away. */
    private function flushHomepageCache(): void
    {
        Cache::forget(self::HOMEPAGE_CACHE_KEY);
    }

    /** Move an uploaded file into the public uploads folder, returning its relative path. */
    private function storeImage($image): string
    {
        // uniqid() keeps a multi-file upload from overwriting itself within the same second.
        $imageName = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
        $uploadDir = public_path(self::UPLOAD_DIR);

        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $image->move($uploadDir, $imageName);

        return self::UPLOAD_DIR . '/' . $imageName;
    }

    /** Remove a previously uploaded file from disk. */
    private function deleteImage(?string $path): void
    {
        if ($path && file_exists(public_path($path))) {
            unlink(public_path($path));
        }
    }
}
