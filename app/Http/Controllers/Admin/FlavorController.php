<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ProductFlavor;
use Toastr;

class FlavorController extends Controller
{
    public function index()
    {
        $show_data = ProductFlavor::orderBy('sort_order')->orderBy('id', 'desc')->get();
        return view('backEnd.flavor.index', compact('show_data'));
    }

    public function create()
    {
        return view('backEnd.flavor.create');
    }

    public function store(Request $request)
    {
        $this->validate($request, ['name' => 'required', 'status' => 'required']);
        ProductFlavor::create($request->only('name', 'status', 'sort_order'));
        Toastr::success('Success', 'Flavor added successfully');
        return redirect()->route('flavors.index');
    }

    public function edit($id)
    {
        $edit_data = ProductFlavor::findOrFail($id);
        return view('backEnd.flavor.edit', compact('edit_data'));
    }

    public function update(Request $request)
    {
        $this->validate($request, ['name' => 'required', 'status' => 'required']);
        $item = ProductFlavor::findOrFail($request->id);
        $item->update($request->only('name', 'status', 'sort_order'));
        Toastr::success('Success', 'Flavor updated successfully');
        return redirect()->route('flavors.index');
    }

    public function inactive(Request $request)
    {
        $item = ProductFlavor::findOrFail($request->hidden_id);
        $item->status = 0;
        $item->save();
        Toastr::success('Success', 'Flavor deactivated');
        return redirect()->back();
    }

    public function active(Request $request)
    {
        $item = ProductFlavor::findOrFail($request->hidden_id);
        $item->status = 1;
        $item->save();
        Toastr::success('Success', 'Flavor activated');
        return redirect()->back();
    }

    public function destroy(Request $request)
    {
        ProductFlavor::findOrFail($request->hidden_id)->delete();
        Toastr::success('Success', 'Flavor deleted');
        return redirect()->back();
    }
}
