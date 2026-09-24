{{-- Shared by create and edit. Expects $pack (KittenPack|null) and $products. --}}
@php
    $pack     = $pack ?? null;
    $oldItems = old('items');

    if ($oldItems === null) {
        $oldItems = $pack
            ? $pack->items->map(fn ($i) => [
                'name'        => $i->name,
                'quantity'    => $i->quantity,
                'is_included' => $i->is_included ? 1 : 0,
            ])->all()
            : [];
    }
@endphp

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <div class="header-icon"><i class="fe-package"></i></div>
                <h5 class="card-title">Pack Details</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-8 mb-3">
                        <label class="form-label">Pack Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                               value="{{ old('name', $pack->name ?? '') }}"
                               placeholder="e.g. Welcome Home Kit - 1 Week Pack" required>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Tier Label</label>
                        <input type="text" name="tier_label" class="form-control"
                               value="{{ old('tier_label', $pack->tier_label ?? '') }}" placeholder="Starter / Affordable / Premium">
                        <small class="text-muted d-block mt-1">Used by the FAQ copy.</small>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Badge</label>
                        <input type="text" name="badge" class="form-control"
                               value="{{ old('badge', $pack->badge ?? '') }}" placeholder="e.g. Most Popular">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Card Theme <span class="text-danger">*</span></label>
                        <select name="theme" class="form-select" required>
                            <option value="light" {{ old('theme', $pack->theme ?? 'light') === 'light' ? 'selected' : '' }}>Light</option>
                            <option value="dark" {{ old('theme', $pack->theme ?? 'light') === 'dark' ? 'selected' : '' }}>Dark (highlighted)</option>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Sort Order</label>
                        <input type="number" name="sort_order" class="form-control"
                               value="{{ old('sort_order', $pack->sort_order ?? 0) }}" min="0">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Price (৳) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" min="0" name="price"
                               class="form-control @error('price') is-invalid @enderror"
                               value="{{ old('price', $pack->price ?? '') }}" required>
                        @error('price')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Old Price (৳)</label>
                        <input type="number" step="0.01" min="0" name="old_price"
                               class="form-control @error('old_price') is-invalid @enderror"
                               value="{{ old('old_price', $pack->old_price ?? '') }}">
                        @error('old_price')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <small class="text-muted d-block mt-1">The "You save" line is worked out from this.</small>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Linked Product</label>
                        <select name="product_id" class="form-select">
                            <option value="">— none (Buy Now uses WhatsApp) —</option>
                            @foreach($products as $product)
                                <option value="{{ $product->id }}" {{ (int) old('product_id', $pack->product_id ?? 0) === $product->id ? 'selected' : '' }}>
                                    {{ $product->name }}
                                </option>
                            @endforeach
                        </select>
                        <small class="text-muted d-block mt-1">Drives cart and checkout.</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <div class="header-icon"><i class="fe-list"></i></div>
                <h5 class="card-title">What's Inside</h5>
            </div>
            <div class="card-body">
                <p class="text-muted font-size-13">
                    List every item the card shows. Untick <strong>Included</strong> to render the row struck through —
                    the "Items" badge adds up the quantities of the ticked rows only.
                </p>

                <div id="pack_items">
                    @forelse($oldItems as $i => $item)
                    <div class="pack-item-row">
                        <input type="text" name="items[{{ $i }}][name]" class="form-control" placeholder="Item name"
                               value="{{ $item['name'] ?? '' }}">
                        <input type="number" name="items[{{ $i }}][quantity]" class="form-control" min="1"
                               value="{{ $item['quantity'] ?? 1 }}" placeholder="Qty">
                        <label class="pack-item-check">
                            <input type="checkbox" name="items[{{ $i }}][is_included]" value="1"
                                   {{ !empty($item['is_included']) ? 'checked' : '' }}>
                            <span>Included</span>
                        </label>
                        <button type="button" class="btn-remove-row" aria-label="Remove item"><i class="fe-x"></i></button>
                    </div>
                    @empty
                    <div class="pack-item-row">
                        <input type="text" name="items[0][name]" class="form-control" placeholder="Item name">
                        <input type="number" name="items[0][quantity]" class="form-control" min="1" value="1" placeholder="Qty">
                        <label class="pack-item-check">
                            <input type="checkbox" name="items[0][is_included]" value="1" checked>
                            <span>Included</span>
                        </label>
                        <button type="button" class="btn-remove-row" aria-label="Remove item"><i class="fe-x"></i></button>
                    </div>
                    @endforelse
                </div>

                <button type="button" id="add_item_row" class="btn btn-light border rounded-pill mt-3 px-4">
                    <i class="fe-plus me-1"></i> Add Item
                </button>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <div class="header-icon"><i class="fe-settings"></i></div>
                <h5 class="card-title">Publish</h5>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-4 p-3 bg-light rounded border border-light">
                    <div>
                        <h6 class="mb-1 text-dark fw-bold">Active Status</h6>
                        <p class="text-muted font-size-12 mb-0">Show on the page?</p>
                    </div>
                    <label class="switch">
                        <input type="checkbox" name="status" value="1" {{ old('status', $pack->status ?? 1) ? 'checked' : '' }}>
                        <span class="slider round"></span>
                    </label>
                </div>
                <button type="submit" class="btn btn-submit w-100 rounded-pill">
                    <i class="fe-save me-1"></i> {{ $submitLabel }}
                </button>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <div class="header-icon"><i class="fe-image"></i></div>
                <h5 class="card-title">Pack Image</h5>
            </div>
            <div class="card-body">
                <div class="image-upload-box" onclick="document.getElementById('image').click()">
                    <input type="file" name="image" id="image" class="d-none" accept="image/*" onchange="readURL(this)">

                    @if($pack && $pack->image)
                        <div id="current_image">
                            <img src="{{ asset('public/'.$pack->image) }}" class="preview-img" alt="Current pack image">
                            <small class="text-muted d-block mt-2">Click to replace</small>
                        </div>
                    @else
                        <div id="upload_placeholder" class="upload-placeholder">
                            <i class="fe-upload-cloud"></i>
                            <p>Click to upload</p>
                            <small class="text-muted d-block mt-1">JPG, PNG, WEBP (Max 2MB)</small>
                        </div>
                    @endif

                    <div id="new_preview" class="d-none">
                        <img id="preview_image" class="preview-img" src="#" alt="Preview">
                    </div>
                </div>
                @error('image')<div class="text-danger small mt-2 text-center">{{ $message }}</div>@enderror
            </div>
        </div>
    </div>
</div>
