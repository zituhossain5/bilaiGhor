<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ProductLifeStage;
use Toastr;

class LifeStageController extends Controller
{
    public function index()
    {
        $show_data = ProductLifeStage::orderBy('sort_order')->orderBy('id', 'desc')->get();
        return view('backEnd.lifestage.index', compact('show_data'));
    }

    public function create()
    {
        return view('backEnd.lifestage.create');
    }

    public function store(Request $request)
    {
        $this->validate($request, ['name' => 'required', 'status' => 'required']);
        ProductLifeStage::create($request->only('name', 'status', 'sort_order'));
        Toastr::success('Success', 'Life Stage added successfully');
        return redirect()->route('lifestages.index');
    }

    public function edit($id)
    {
        $edit_data = ProductLifeStage::findOrFail($id);
        return view('backEnd.lifestage.edit', compact('edit_data'));
    }

    public function update(Request $request)
    {
        $this->validate($request, ['name' => 'required', 'status' => 'required']);
        $item = ProductLifeStage::findOrFail($request->id);
        $item->update($request->only('name', 'status', 'sort_order'));
        Toastr::success('Success', 'Life Stage updated successfully');
        return redirect()->route('lifestages.index');
    }

    public function inactive(Request $request)
    {
        $item = ProductLifeStage::findOrFail($request->hidden_id);
        $item->status = 0;
        $item->save();
        Toastr::success('Success', 'Life Stage deactivated');
        return redirect()->back();
    }

    public function active(Request $request)
    {
        $item = ProductLifeStage::findOrFail($request->hidden_id);
        $item->status = 1;
        $item->save();
        Toastr::success('Success', 'Life Stage activated');
        return redirect()->back();
    }

    public function destroy(Request $request)
    {
        ProductLifeStage::findOrFail($request->hidden_id)->delete();
        Toastr::success('Success', 'Life Stage deleted');
        return redirect()->back();
    }
}
