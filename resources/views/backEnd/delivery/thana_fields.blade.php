<div class="row">
    <div class="col-md-6 mb-3"><label class="form-label">Name *</label><input type="text" name="name" value="{{ old('name', $data?->name) }}" class="form-control" required maxlength="190"></div>
    <div class="col-md-6 mb-3"><label class="form-label">Bangla Name</label><input type="text" name="name_bn" value="{{ old('name_bn', $data?->name_bn) }}" class="form-control" maxlength="190"></div>
    <div class="col-md-4 mb-3"><label class="form-label">Post Code</label><input type="text" name="post_code" value="{{ old('post_code', $data?->post_code) }}" class="form-control" maxlength="20"></div>
    <div class="col-md-4 mb-3"><label class="form-label">Delivery Charge *</label><input type="number" name="delivery_charge" value="{{ old('delivery_charge', $data?->delivery_charge ?? 0) }}" class="form-control" min="0" step="0.01" required></div>
    <div class="col-md-4 mb-3"><label class="form-label">Sort Order</label><input type="number" name="sort_order" value="{{ old('sort_order', $data?->sort_order ?? 0) }}" class="form-control" min="0" step="1"></div>
</div>
<div class="form-check mb-3"><input class="form-check-input" type="checkbox" name="status" value="1" id="thana-status" @checked(old('status', $data?->status ?? 1))><label class="form-check-label" for="thana-status">Active</label></div>
