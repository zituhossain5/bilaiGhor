@extends('backEnd.layouts.master')
@section('title','Manage Weights')
@section('css')
<link href="{{asset('/public/backEnd/')}}/assets/libs/datatables.net-bs5/css/dataTables.bootstrap5.min.css" rel="stylesheet" type="text/css" />
<link href="{{asset('/public/backEnd/')}}/assets/libs/datatables.net-responsive-bs5/css/responsive.bootstrap5.min.css" rel="stylesheet" type="text/css" />
<link href="{{asset('/public/backEnd/')}}/assets/libs/datatables.net-buttons-bs5/css/buttons.bootstrap5.min.css" rel="stylesheet" type="text/css" />
<style>
    .card { border: none; box-shadow: 0 0 20px rgba(18,38,63,.03); border-radius: 12px; overflow: hidden; }
    .card-body { padding: 25px; }
    .table thead th { background-color: #f9fbfd; font-weight: 600; text-transform: uppercase; font-size: 11px; color: #8391a2; letter-spacing: .5px; border-bottom: 1px solid #eef2f7; padding: 12px 15px; }
    .table tbody td { vertical-align: middle; padding: 15px; border-bottom: 1px solid #f1f5f7; color: #313b5e; font-size: 14px; }
    .attr-badge { font-weight: 600; font-size: 13px; padding: 6px 12px; border-radius: 6px; background-color: #f8f9fa; color: #343a40; border: 1px solid #eef2f7; display: inline-block; }
    .badge-soft-success { background-color: rgba(10,207,151,.18); color: #0acf97; }
    .badge-soft-danger { background-color: rgba(250,92,124,.18); color: #fa5c7c; }
    .badge-pill { padding: 5px 10px; border-radius: 50rem; font-weight: 500; font-size: 11px; }
    .action-btn { width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center; border-radius: 50%; color: #6c757d; transition: all .2s; border: 1px solid transparent; background: #f9fbfd; }
    .action-btn:hover { background-color: #eef2f7; color: #343a40; }
    .btn-edit:hover { background-color: rgba(114,124,245,.1); color: #727cf5; }
    .btn-delete:hover { background-color: rgba(250,92,124,.1); color: #fa5c7c; }
    .btn-active:hover { background-color: rgba(10,207,151,.1); color: #0acf97; }
    .btn-inactive:hover { background-color: rgba(255,188,0,.1); color: #ffbc00; }
</style>
@endsection
@section('content')
<div class="container-fluid">
    <div class="row mb-3 mt-3">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <h4 class="page-title mb-0" style="font-weight:700;color:#2d3436;">Product Weights</h4>
            <a href="{{route('weights.create')}}" class="btn btn-primary rounded-pill shadow-sm px-4"><i class="fe-plus me-1"></i> Add Weight</a>
        </div>
    </div>
    <div class="row"><div class="col-12"><div class="card"><div class="card-body">
        <table id="datatable-buttons" class="table table-hover w-100 dt-responsive nowrap">
            <thead><tr><th style="width:50px;">SL</th><th>Weight Name</th><th>Sort Order</th><th>Status</th><th class="text-end" style="width:150px;">Action</th></tr></thead>
            <tbody>
                @foreach($show_data as $key => $value)
                <tr>
                    <td>{{$loop->iteration}}</td>
                    <td><span class="attr-badge">{{$value->name}}</span></td>
                    <td>{{$value->sort_order ?? '—'}}</td>
                    <td>
                        @if($value->status==1)<span class="badge badge-pill badge-soft-success">Active</span>
                        @else<span class="badge badge-pill badge-soft-danger">Inactive</span>@endif
                    </td>
                    <td class="text-end"><div class="d-inline-flex gap-2">
                        @if($value->status==1)
                        <form method="post" action="{{route('weights.inactive')}}" class="d-inline">@csrf<input type="hidden" value="{{$value->id}}" name="hidden_id"><button type="submit" class="action-btn btn-inactive" title="Deactivate"><i class="fe-eye-off"></i></button></form>
                        @else
                        <form method="post" action="{{route('weights.active')}}" class="d-inline">@csrf<input type="hidden" value="{{$value->id}}" name="hidden_id"><button type="submit" class="action-btn btn-active" title="Activate"><i class="fe-eye"></i></button></form>
                        @endif
                        <a href="{{route('weights.edit',$value->id)}}" class="action-btn btn-edit" title="Edit"><i class="fe-edit"></i></a>
                        <form method="post" action="{{route('weights.destroy')}}" class="d-inline">@csrf<input type="hidden" name="hidden_id" value="{{$value->id}}"><button type="submit" class="action-btn btn-delete delete-confirm" title="Delete"><i class="fe-trash-2"></i></button></form>
                    </div></td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div></div></div></div>
</div>
@endsection
@section('script')
<script src="{{asset('/public/backEnd/')}}/assets/libs/datatables.net/js/jquery.dataTables.min.js"></script>
<script src="{{asset('/public/backEnd/')}}/assets/libs/datatables.net-bs5/js/dataTables.bootstrap5.min.js"></script>
<script src="{{asset('/public/backEnd/')}}/assets/libs/datatables.net-responsive/js/dataTables.responsive.min.js"></script>
<script src="{{asset('/public/backEnd/')}}/assets/libs/datatables.net-responsive-bs5/js/responsive.bootstrap5.min.js"></script>
<script src="{{asset('/public/backEnd/')}}/assets/libs/datatables.net-buttons/js/dataTables.buttons.min.js"></script>
<script src="{{asset('/public/backEnd/')}}/assets/libs/datatables.net-buttons-bs5/js/buttons.bootstrap5.min.js"></script>
<script src="{{asset('/public/backEnd/')}}/assets/libs/datatables.net-buttons/js/buttons.html5.min.js"></script>
<script src="{{asset('/public/backEnd/')}}/assets/libs/datatables.net-buttons/js/buttons.print.min.js"></script>
<script src="{{asset('/public/backEnd/')}}/assets/libs/pdfmake/build/pdfmake.min.js"></script>
<script src="{{asset('/public/backEnd/')}}/assets/libs/pdfmake/build/vfs_fonts.js"></script>
<script src="{{asset('/public/backEnd/')}}/assets/js/pages/datatables.init.js"></script>
@endsection
