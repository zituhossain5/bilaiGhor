<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ProductWeight;
use Toastr;

class WeightController extends Controller
{
    public function index()
    {
        $show_data = ProductWeight::orderBy('sort_order')->orderBy('id', 'desc')->get();
        return view('backEnd.weight.index', compact('show_data'));
    }

    public function create()
    {
        return view('backEnd.weight.create');
    }

    public function store(Request $request)
    {
        $this->validate($request, ['name' => 'required', 'status' => 'required']);
        ProductWeight::create($request->only('name', 'status', 'sort_order'));
        Toastr::success('Success', 'Weight added successfully');
        return redirect()->route('weights.index');
    }

    public function edit($id)
    {
        $edit_data = ProductWeight::findOrFail($id);
        return view('backEnd.weight.edit', compact('edit_data'));
    }

    public function update(Request $request)
    {
        $this->validate($request, ['name' => 'required', 'status' => 'required']);
        $item = ProductWeight::findOrFail($request->id);
        $item->update($request->only('name', 'status', 'sort_order'));
        Toastr::success('Success', 'Weight updated successfully');
        return redirect()->route('weights.index');
    }

    public function inactive(Request $request)
    {
        $item = ProductWeight::findOrFail($request->hidden_id);
        $item->status = 0;
        $item->save();
        Toastr::success('Success', 'Weight deactivated');
        return redirect()->back();
    }

    public function active(Request $request)
    {
        $item = ProductWeight::findOrFail($request->hidden_id);
        $item->status = 1;
        $item->save();
        Toastr::success('Success', 'Weight activated');
        return redirect()->back();
    }

    public function destroy(Request $request)
    {
        ProductWeight::findOrFail($request->hidden_id)->delete();
        Toastr::success('Success', 'Weight deleted');
        return redirect()->back();
    }
}
