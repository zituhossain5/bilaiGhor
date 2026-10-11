@extends('backEnd.layouts.master')
{{-- Add (manual customer) and Edit (manual or registered). $edit_data is null when adding. --}}
@php
    $isNew = ! $edit_data;
    $isRegistered = ! $isNew && ! $edit_data->isManual();
    $val = fn ($field) => old($field, $isNew ? null : $edit_data->{$field});
    $isActive = old('status', $isNew ? 1 : ($edit_data->status === 'active' ? 1 : 0));
@endphp
@section('title', $isNew ? 'Add Customer' : 'Edit Customer')

@section('css')
<style>
    /* Premium Card */
    .card {
        border: none;
        box-shadow: 0 0 20px rgba(18, 38, 63, 0.03);
        border-radius: 12px;
        overflow: hidden;
    }
    .card-header {
        background: #fff;
        border-bottom: 1px solid #f1f5f7;
        padding: 20px 25px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .card-title {
        font-size: 16px;
        font-weight: 700;
        color: #2d3436;
        margin: 0;
    }
    .header-icon {
        width: 35px;
        height: 35px;
        background: rgba(114, 124, 245, 0.1);
        color: #727cf5;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 16px;
    }

    /* Form Styles */
    .form-label {
        font-weight: 600;
        font-size: 13px;
        color: #636e72;
        margin-bottom: 8px;
    }
    .form-control, .form-select {
        background-color: #fbfcff;
        border: 1px solid #eef2f7;
        padding: 12px 15px;
        border-radius: 8px;
        font-size: 14px;
        color: #2d3436;
        transition: all 0.3s;
    }
    .form-control:focus, .form-select:focus {
        background-color: #fff;
        border-color: #727cf5;
        box-shadow: 0 0 0 4px rgba(114, 124, 245, 0.1);
    }
    .optional-note { font-weight: 400; color: #98a6ad; font-size: 12px; }

    /* Image Preview */
    .image-preview-container {
        width: 100px;
        height: 100px;
        border-radius: 50%;
        overflow: hidden;
        border: 2px solid #eef2f7;
        margin-top: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    }
    .image-preview-container img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    /* Toggle Switch */
    .switch { position: relative; display: inline-block; width: 46px; height: 24px; }
    .switch input { opacity: 0; width: 0; height: 0; }
    .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #eef2f7; transition: .4s; border-radius: 34px; border: 1px solid #dee2e6; }
    .slider:before { position: absolute; content: ""; height: 18px; width: 18px; left: 2px; bottom: 2px; background-color: white; transition: .4s; border-radius: 50%; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
    input:checked + .slider { background-color: #0acf97; border-color: #0acf97; }
    input:checked + .slider:before { transform: translateX(22px); }

    .source-badge { font-size: 11px; font-weight: 600; padding: 4px 10px; border-radius: 50rem; }
    .source-manual { background: rgba(255, 188, 0, 0.15); color: #b98900; }
    .source-registered { background: rgba(57, 175, 209, 0.15); color: #2a8fad; }
    .dup-warning { font-size: 13px; }

    /* Button Style */
    .btn-submit {
        background: linear-gradient(45deg, #0acf97, #06b6d4);
        border: none;
        color: white;
        padding: 12px 25px;
        font-weight: 600;
        letter-spacing: 0.5px;
        box-shadow: 0 4px 15px rgba(10, 207, 151, 0.3);
        transition: 0.3s;
        border-radius: 50rem;
    }
    .btn-submit:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(10, 207, 151, 0.4);
    }
</style>
@endsection

@section('content')
<div class="container-fluid">

    <div class="row mb-3 mt-3">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <div>
                <h4 class="page-title mb-0" style="font-weight: 700; color: #2d3436;">
                    {{ $isNew ? 'Add Customer' : 'Edit Customer' }}
                    @unless($isNew)
                        <span class="source-badge {{ $isRegistered ? 'source-registered' : 'source-manual' }} align-middle ms-1">{{ $isRegistered ? 'Registered' : 'Manual' }}</span>
                    @endunless
                </h4>
                <p class="text-muted font-size-13 mb-0">
                    {{ $isRegistered ? 'Update customer information.' : 'All fields are optional. Manual customers are records only — they cannot log in.' }}
                </p>
            </div>
            <a href="{{route('customers.index')}}" class="btn btn-light rounded-pill border shadow-sm px-4">
                <i class="fe-arrow-left me-1"></i> Back to List
            </a>
        </div>
    </div>

    @if($errors->any())
        <div class="alert alert-danger">
            <strong>Please fix the highlighted fields.</strong>
            <ul class="mb-0 mt-2">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header">
                    <div class="header-icon"><i class="fe-user-check"></i></div>
                    <h5 class="card-title">Customer Details</h5>
                </div>
                <div class="card-body">
                    <form action="{{ $isNew ? route('customers.store') : route('customers.update') }}" method="POST" enctype="multipart/form-data" novalidate>
                        @csrf
                        @unless($isNew)
                            <input type="hidden" value="{{$edit_data->id}}" name="hidden_id">
                        @endunless

                        <div class="row">
                            <div class="col-md-8">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group mb-4">
                                            <label class="form-label">Full Name @if($isRegistered)<span class="text-danger">*</span>@endif</label>
                                            <input type="text" class="form-control" name="name" value="{{ $val('name') }}" maxlength="155">
                                            @error('name') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group mb-4">
                                            <label class="form-label">Mobile Number @if($isRegistered)<span class="text-danger">*</span>@endif</label>
                                            <input type="text" class="form-control" name="phone" id="customer_phone" value="{{ $val('phone') }}" maxlength="55" inputmode="tel" placeholder="01XXXXXXXXX">
                                            @error('phone') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                            <div id="phone-duplicate" class="dup-warning text-warning mt-1" hidden></div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group mb-4">
                                            <label class="form-label">Email Address</label>
                                            <input type="email" class="form-control" name="email" value="{{ $val('email') }}" maxlength="55">
                                            @error('email') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                        </div>
                                    </div>
                                    @if($isRegistered)
                                    <div class="col-md-6">
                                        <div class="form-group mb-4">
                                            <label class="form-label">Password <span class="optional-note">(Leave blank to keep current)</span></label>
                                            <input type="password" class="form-control" name="password" placeholder="********" autocomplete="new-password">
                                            @error('password') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                        </div>
                                    </div>
                                    @endif
                                    <div class="col-md-12">
                                        <div class="form-group mb-4">
                                            <label class="form-label">Delivery Address</label>
                                            <textarea class="form-control" name="address" rows="2" maxlength="255">{{ $val('address') }}</textarea>
                                            @error('address') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group mb-4">
                                            <label class="form-label">District</label>
                                            <select name="district_id" id="customer_district" class="form-select">
                                                <option value="">Select District</option>
                                                @foreach($districts as $district)
                                                    <option value="{{ $district->id }}" @selected((string) $val('district_id') === (string) $district->id)>{{ $district->name }}</option>
                                                @endforeach
                                            </select>
                                            @error('district_id') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group mb-4">
                                            <label class="form-label">Thana</label>
                                            <select name="thana_id" id="customer_thana" class="form-select" disabled>
                                                <option value="">Select District First</option>
                                            </select>
                                            @error('thana_id') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group mb-4">
                                            <label class="form-label">Post Code</label>
                                            <input type="text" class="form-control" name="post_code" id="customer_post_code" value="{{ $val('post_code') }}" maxlength="20">
                                            @error('post_code') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="form-group mb-4">
                                            <label class="form-label">Notes <span class="optional-note">(admin only)</span></label>
                                            <textarea class="form-control" name="notes" rows="3" maxlength="2000">{{ $val('notes') }}</textarea>
                                            @error('notes') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="card bg-light border-0">
                                    <div class="card-body text-center">
                                        @if($isRegistered)
                                            <label class="form-label mb-3">Profile Picture</label>

                                            <div class="mx-auto image-preview-container">
                                                <img src="{{asset($edit_data->image)}}" alt="user-image" id="preview-image">
                                            </div>

                                            <div class="mt-3">
                                                <input type="file" class="form-control form-control-sm" name="image" id="upload-image" accept="image/jpeg,image/png,image/webp">
                                                <small class="text-muted d-block mt-2">Allowed: jpg, jpeg, png, webp</small>
                                            </div>
                                            @error('image') <div class="text-danger small mt-1">{{ $message }}</div> @enderror

                                            <hr class="my-4">
                                        @endif

                                        <div class="d-flex justify-content-between align-items-center text-start">
                                            <div>
                                                <h6 class="mb-0 fw-bold">Active Status</h6>
                                                <small class="text-muted">{{ $isRegistered ? 'Enable account access' : 'Show as active in lists' }}</small>
                                            </div>
                                            <label class="switch">
                                                <input type="hidden" name="status" value="0">
                                                <input type="checkbox" name="status" value="1" @checked((int) $isActive === 1)>
                                                <span class="slider round"></span>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row mt-3">
                            <div class="col-12 text-end">
                                <button type="submit" class="btn btn-submit">
                                    <i class="fe-check-circle me-1"></i> {{ $isNew ? 'Save Customer' : 'Update Customer' }}
                                </button>
                            </div>
                        </div>

                    </form>
                </div> </div> </div> </div>
</div>
@endsection

@section('script')
@include('backEnd.partials.thana-loader')
<script>
(function () {
    var district = document.getElementById('customer_district');
    var thana = document.getElementById('customer_thana');
    var postCode = document.getElementById('customer_post_code');

    function fillPostCode() {
        var option = thana.options[thana.selectedIndex];
        if (option && option.value && !postCode.value) postCode.value = option.dataset.postCode || '';
    }

    district.addEventListener('change', function () { BgThanaLoader.load(thana, district.value, null); });
    thana.addEventListener('change', fillPostCode);
    BgThanaLoader.load(thana, district.value, @json((string) $val('thana_id')));

    // Same number on another customer: warn with a link, never block saving.
    var phone = document.getElementById('customer_phone');
    var box = document.getElementById('phone-duplicate');
    var currentId = @json($isNew ? null : $edit_data->id);
    var editUrl = @json(route('customers.edit', ['id' => '__ID__']));
    var timer = null;

    function checkDuplicate() {
        var value = phone.value.trim();
        box.hidden = true;
        box.textContent = '';
        if (value.replace(/\D/g, '').length < 10) return;

        fetch(@json(route('customers.search')) + '?phone=' + encodeURIComponent(value), { headers: { 'Accept': 'application/json' } })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (phone.value.trim() !== value) return;
                var others = (data.results || []).filter(function (c) { return c.exact && c.id !== currentId; });
                if (!others.length) return;
                box.appendChild(document.createTextNode('This number already belongs to: '));
                others.forEach(function (c, i) {
                    if (i) box.appendChild(document.createTextNode(', '));
                    var link = document.createElement('a');
                    link.href = editUrl.replace('__ID__', c.id);
                    link.target = '_blank';
                    link.textContent = (c.name || 'Unnamed') + (c.manual ? ' (Manual)' : '');
                    box.appendChild(link);
                });
                box.appendChild(document.createTextNode('. You can still save.'));
                box.hidden = false;
            })
            .catch(function () {});
    }

    phone.addEventListener('input', function () { clearTimeout(timer); timer = setTimeout(checkDuplicate, 400); });
    checkDuplicate();

    var upload = document.getElementById('upload-image');
    if (upload) {
        upload.addEventListener('change', function (event) {
            if (!event.target.files[0]) return;
            var reader = new FileReader();
            reader.onload = function () { document.getElementById('preview-image').src = reader.result; };
            reader.readAsDataURL(event.target.files[0]);
        });
    }
}());
</script>
@endsection
