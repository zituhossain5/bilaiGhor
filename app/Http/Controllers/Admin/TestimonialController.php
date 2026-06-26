<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Testimonial;
use Illuminate\Http\Request;

class TestimonialController extends Controller
{
    public function index()
    {
        $testimonials = Testimonial::orderBy('sort_order')->orderBy('id', 'desc')->get();
        return view('backEnd.testimonial.index', compact('testimonials'));
    }

    public function create()
    {
        return view('backEnd.testimonial.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'       => 'required|string|max:255',
            'location'   => 'nullable|string|max:255',
            'rating'     => 'required|integer|min:1|max:5',
            'message'    => 'required|string',
            'image'      => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'sort_order' => 'nullable|integer',
        ]);

        $imagePath = null;

        if ($request->hasFile('image')) {
            $image     = $request->file('image');
            $imageName = time() . '.' . $image->getClientOriginalExtension();
            $uploadDir = public_path('uploads/testimonials');

            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $image->move($uploadDir, $imageName);
            $imagePath = 'uploads/testimonials/' . $imageName;
        }

        Testimonial::create([
            'name'       => $request->name,
            'location'   => $request->location,
            'rating'     => $request->rating,
            'message'    => $request->message,
            'image'      => $imagePath,
            'status'     => $request->status ? 1 : 0,
            'sort_order' => $request->sort_order ?? 0,
        ]);

        return redirect()->route('admin.testimonial.index')
            ->with('success', 'Testimonial created successfully');
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
            'name'       => 'required|string|max:255',
            'location'   => 'nullable|string|max:255',
            'rating'     => 'required|integer|min:1|max:5',
            'message'    => 'required|string',
            'image'      => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'sort_order' => 'nullable|integer',
        ]);

        if ($request->hasFile('image')) {
            if ($testimonial->image && file_exists(public_path($testimonial->image))) {
                unlink(public_path($testimonial->image));
            }

            $image     = $request->file('image');
            $imageName = time() . '.' . $image->getClientOriginalExtension();
            $uploadDir = public_path('uploads/testimonials');

            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $image->move($uploadDir, $imageName);
            $testimonial->image = 'uploads/testimonials/' . $imageName;
        }

        $testimonial->update([
            'name'       => $request->name,
            'location'   => $request->location,
            'rating'     => $request->rating,
            'message'    => $request->message,
            'status'     => $request->status ? 1 : 0,
            'sort_order' => $request->sort_order ?? 0,
        ]);

        return redirect()->route('admin.testimonial.index')
            ->with('success', 'Testimonial updated successfully');
    }

    public function delete($id)
    {
        $testimonial = Testimonial::findOrFail($id);

        if ($testimonial->image && file_exists(public_path($testimonial->image))) {
            unlink(public_path($testimonial->image));
        }

        $testimonial->delete();

        return back()->with('success', 'Testimonial deleted successfully');
    }
}
