<div class="card pf-sidebar-panel mb-4">
    <div class="card-body">
        <div class="pf-side-block">
            <div class="pf-side-head"><i class="fe-dollar-sign"></i> Pricing & Inventory</div>
            <div class="row g-compact">
                <div class="col-6">
                    <label for="purchase_price" class="form-label">Purchase <small class="text-muted">(Opt.)</small></label>
                    <input type="text" class="form-control border-primary <?php $__errorArgs = ['purchase_price'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                           name="purchase_price" value="<?php echo e($edit_data->purchase_price); ?>" id="purchase_price" placeholder="0" />
                    <?php $__errorArgs = ['purchase_price'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><span class="invalid-feedback"><strong><?php echo e($message); ?></strong></span><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>
                <div class="col-6">
                    <label for="old_price" class="form-label">Old Price</label>
                    <input type="text" class="form-control <?php $__errorArgs = ['old_price'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                           name="old_price" value="<?php echo e($edit_data->old_price); ?>" id="old_price" />
                    <?php $__errorArgs = ['old_price'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><span class="invalid-feedback"><strong><?php echo e($message); ?></strong></span><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>
                <div class="col-6">
                    <label for="new_price" class="form-label">New <small class="text-muted">(Opt.)</small></label>
                    <input type="text" class="form-control font-weight-bold <?php $__errorArgs = ['new_price'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                           name="new_price" value="<?php echo e($edit_data->new_price); ?>" id="new_price" placeholder="0" />
                    <?php $__errorArgs = ['new_price'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><span class="invalid-feedback"><strong><?php echo e($message); ?></strong></span><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>
                <div class="col-6">
                    <label for="reseller_price" class="form-label">Reseller</label>
                    <input type="text" step="0.01" class="form-control <?php $__errorArgs = ['reseller_price'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                           name="reseller_price" value="<?php echo e(old('reseller_price', $edit_data->reseller_price)); ?>" id="reseller_price" placeholder="Optional" />
                    <?php $__errorArgs = ['reseller_price'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><span class="invalid-feedback"><strong><?php echo e($message); ?></strong></span><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>
                <div class="col-6">
                    <label for="stock" class="form-label">Stock <small class="text-muted">(Opt.)</small></label>
                    <input type="text" class="form-control <?php $__errorArgs = ['stock'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                           name="stock" value="<?php echo e($edit_data->stock); ?>" id="stock" placeholder="0" />
                    <?php $__errorArgs = ['stock'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><span class="invalid-feedback"><strong><?php echo e($message); ?></strong></span><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>
                <div class="col-6">
                    <label for="pro_unit" class="form-label">Unit</label>
                    <input type="text" class="form-control <?php $__errorArgs = ['pro_unit'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                           name="pro_unit" value="<?php echo e($edit_data->pro_unit); ?>" id="pro_unit" />
                </div>
                <div class="col-12">
                    <label for="brand_id" class="form-label">Brand</label>
                    <select class="form-control form-control-sm select2 <?php $__errorArgs = ['brand_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" name="brand_id">
                        <option value="">Select..</option>
                        <?php $__currentLoopData = $brands; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($value->id); ?>" <?php if($edit_data->brand_id==$value->id): ?> selected <?php endif; ?>><?php echo e($value->name); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                    <?php $__errorArgs = ['brand_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><span class="invalid-feedback"><strong><?php echo e($message); ?></strong></span><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>
            </div>
        </div>

        <div class="pf-side-block">
            <div class="pf-side-head"><i class="fe-image"></i> Media & Video</div>
            <label class="form-label">Gallery</label>
            <div class="increment-wrapper">
                <div class="control-group increment image-row">
                    <div class="row align-items-center g-1">
                        <div class="col-9">
                            <input type="file" name="image[]" class="form-control form-control-sm <?php $__errorArgs = ['image'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" accept="image/*" />
                        </div>
                        <div class="col-3">
                            <button class="btn btn-success btn-increment btn-sm w-100" type="button"><i class="fa fa-plus"></i></button>
                        </div>
                    </div>
                    <?php $__errorArgs = ['image'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><span class="invalid-feedback"><strong><?php echo e($message); ?></strong></span><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>
            </div>
            <div class="clone hide d-none" style="display: none;">
                <div class="control-group image-row">
                    <div class="row align-items-center g-1">
                        <div class="col-9">
                            <input type="file" name="image[]" class="form-control form-control-sm" accept="image/*" />
                        </div>
                        <div class="col-3">
                            <button class="btn btn-danger btn-remove-image btn-sm w-100" type="button"><i class="fa fa-trash"></i></button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="product_img mt-1 d-flex flex-wrap">
                <?php $__currentLoopData = $edit_data->images->filter(fn($img) => !$img->color_id && !$img->size_id); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $image): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div class="position-relative me-1 mb-1">
                        <img src="<?php echo e(asset($image->image)); ?>" class="edit-image border" alt="">
                        <a href="<?php echo e(route('products.image.destroy',['id'=>$image->id])); ?>"
                           class="btn btn-xs btn-danger position-absolute top-0 end-0 rounded-circle"
                           style="padding: 0px 4px; top: -5px; right: -5px;"
                           onclick="return confirm('Delete this image?')"><i class="mdi mdi-close"></i></a>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
            <?php $colorSizeImages = $edit_data->images->filter(fn($img) => $img->color_id || $img->size_id); ?>
            <?php if($colorSizeImages->isNotEmpty()): ?>
            <div class="mt-1">
                <label class="form-label small text-muted mb-0">Color/Size imgs</label>
                <div class="d-flex flex-wrap gap-1 product_img">
                    <?php $__currentLoopData = $colorSizeImages; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $img): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <div class="position-relative">
                            <img src="<?php echo e(asset($img->image)); ?>" class="edit-image border" alt="">
                            <a href="<?php echo e(route('products.image.destroy',['id'=>$img->id])); ?>" class="btn btn-xs btn-danger position-absolute top-0 end-0 rounded-circle" style="padding:0 4px;top:-5px;right:-5px;" onclick="return confirm('Delete?')"><i class="mdi mdi-close"></i></a>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
            </div>
            <?php endif; ?>

            <?php $existingVideoType = $edit_data->pro_video_type ?? ($edit_data->pro_video ? 'youtube' : null); ?>
            <label class="form-label mt-2 mb-1">প্রোডাক্ট ভিডিও</label>
            <div class="d-flex pf-video-radios flex-wrap">
                <div class="form-check form-check-inline mb-0">
                    <input class="form-check-input" type="radio" name="pro_video_source" id="vs_yt_e" value="youtube" <?php echo e($existingVideoType !== 'upload' ? 'checked' : ''); ?>>
                    <label class="form-check-label" for="vs_yt_e"><i class="fa fa-youtube-play text-danger"></i> YouTube</label>
                </div>
                <div class="form-check form-check-inline mb-0">
                    <input class="form-check-input" type="radio" name="pro_video_source" id="vs_up_e" value="upload" <?php echo e($existingVideoType === 'upload' ? 'checked' : ''); ?>>
                    <label class="form-check-label" for="vs_up_e"><i class="fa fa-upload text-primary"></i> Upload</label>
                </div>
            </div>
            <div id="yt_section_e" style="<?php echo e($existingVideoType === 'upload' ? 'display:none;' : ''); ?>">
                <input type="text" name="pro_video" id="pro_video_e" class="form-control form-control-sm <?php $__errorArgs = ['pro_video'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                       value="<?php echo e($edit_data->pro_video); ?>" placeholder="YouTube URL / Video ID">
                <?php $__errorArgs = ['pro_video'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><span class="invalid-feedback"><strong><?php echo e($message); ?></strong></span><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                <?php if($edit_data->pro_video): ?>
                <div id="yt_preview_e" class="mt-1">
                    <iframe id="yt_iframe_e" width="100%" height="100" src="https://www.youtube.com/embed/<?php echo e($edit_data->pro_video); ?>" frameborder="0" allowfullscreen style="border-radius:8px;"></iframe>
                </div>
                <?php else: ?>
                <div id="yt_preview_e" class="mt-1" style="display:none;">
                    <iframe id="yt_iframe_e" width="100%" height="100" src="" frameborder="0" allowfullscreen style="border-radius:8px;"></iframe>
                </div>
                <?php endif; ?>
            </div>
            <div id="up_section_e" style="<?php echo e($existingVideoType === 'upload' ? '' : 'display:none;'); ?>">
                <?php if($existingVideoType === 'upload' && $edit_data->pro_video_path): ?>
                <div class="mb-1 p-1 bg-light rounded d-flex align-items-center gap-1" style="font-size:11px;">
                    <i class="fa fa-film text-primary"></i>
                    <span class="text-truncate"><?php echo e(basename($edit_data->pro_video_path)); ?></span>
                    <a href="<?php echo e(asset($edit_data->pro_video_path)); ?>" target="_blank" class="btn btn-xs btn-outline-primary ms-auto py-0 px-1"><i class="fa fa-play"></i></a>
                </div>
                <?php endif; ?>
                <input type="file" name="pro_video_file" id="pro_video_file_e" class="form-control form-control-sm" accept="video/mp4,video/webm,video/ogg">
                <div id="up_preview_e" class="mt-1" style="display:none;">
                    <video id="up_video_e" width="100%" height="100" controls style="border-radius:8px;background:#000;"></video>
                </div>
            </div>
        </div>

        <?php
            $currentType = old('product_type', $edit_data->is_digital ? 'digital' : 'physical');
            $isDigital   = $currentType === 'digital';
        ?>
        <div class="pf-side-block">
            <div class="pf-side-head"><i class="fe-settings"></i> Settings</div>
            <div class="row g-compact">
                <div class="col-6">
                    <label for="product_type" class="form-label">Type</label>
                    <select class="form-control form-control-sm bg-light" id="product_type" name="product_type">
                        <option value="physical" <?php echo e($currentType === 'physical' ? 'selected' : ''); ?>>Physical</option>
                        <option value="digital" <?php echo e($currentType === 'digital' ? 'selected' : ''); ?>>Digital</option>
                    </select>
                </div>
                <div id="advance_area" class="col-6" style="<?php echo e($isDigital ? 'display:none;' : ''); ?>">
                    <label for="advance_amount" class="form-label">Advance</label>
                    <input type="text" class="form-control form-control-sm <?php $__errorArgs = ['advance_amount'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                           name="advance_amount" id="advance_amount" value="<?php echo e(old('advance_amount', $edit_data->advance_amount)); ?>" />
                </div>
                <div class="col-6">
                    <label for="sold" class="form-label">Sold</label>
                    <input type="text" class="form-control form-control-sm <?php $__errorArgs = ['sold'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                           name="sold" value="<?php echo e($edit_data->sold); ?>" id="sold" />
                </div>
            </div>
            <div class="d-flex align-items-center pf-free-delivery mb-2">
                <label class="switch me-2 mb-0">
                    <input type="checkbox" value="1" name="free_delivery" <?php echo e(old('free_delivery', $edit_data->free_delivery) ? 'checked' : ''); ?>>
                    <span class="slider round"></span>
                </label>
                <small class="text-muted mb-0">Free delivery</small>
            </div>
            <div id="digital_area" style="<?php echo e($isDigital ? '' : 'display:none;'); ?>" class="p-2 border rounded mb-2 bg-light">
                <?php if($edit_data->digital_file): ?>
                    <small class="d-block text-truncate mb-1">File: <code><?php echo e($edit_data->digital_file); ?></code></small>
                <?php endif; ?>
                <input type="file" class="form-control form-control-sm mb-1" name="digital_file" id="digital_file">
                <div class="row g-1">
                    <div class="col-6">
                        <label class="form-label"><small>Limit</small></label>
                        <input type="number" class="form-control form-control-sm" name="download_limit" id="download_limit"
                               value="<?php echo e(old('download_limit', $edit_data->download_limit ?? 5)); ?>" min="1">
                    </div>
                    <div class="col-6">
                        <label class="form-label"><small>Days</small></label>
                        <input type="number" class="form-control form-control-sm" name="download_expire_days" id="download_expire_days"
                               value="<?php echo e(old('download_expire_days', $edit_data->download_expire_days ?? 7)); ?>" min="1">
                    </div>
                </div>
            </div>
            <div class="row text-center g-compact">
                <div class="col-3">
                    <label class="d-block form-label">Status</label>
                    <label class="switch"><input type="checkbox" value="1" name="status" <?php if($edit_data->status==1): ?> checked <?php endif; ?>><span class="slider round"></span></label>
                </div>
                <div class="col-3">
                    <label class="d-block form-label">Hot</label>
                    <label class="switch"><input type="checkbox" value="1" name="topsale" <?php if($edit_data->topsale==1): ?> checked <?php endif; ?>><span class="slider round"></span></label>
                </div>
                <div class="col-3">
                    <label class="d-block form-label">Flash</label>
                    <label class="switch"><input type="checkbox" value="1" name="flashsale" <?php if($edit_data->flashsale==1): ?> checked <?php endif; ?>><span class="slider round"></span></label>
                </div>
                <div class="col-3">
                    <label class="d-block form-label">Best Seller</label>
                    <label class="switch"><input type="checkbox" value="1" name="best_seller" id="bs_toggle_edit" <?php if($edit_data->best_seller==1): ?> checked <?php endif; ?>><span class="slider round"></span></label>
                </div>
            </div>
            <div id="bs_sold_wrap_edit" style="<?php echo e($edit_data->best_seller==1 ? '' : 'display:none;'); ?>" class="mt-2">
                <label class="form-label">Best Seller Sold Count</label>
                <input type="number" name="best_seller_sold_count" id="bs_sold_edit" class="form-control form-control-sm" min="0"
                       value="<?php echo e(old('best_seller_sold_count', $edit_data->best_seller_sold_count ?? 0)); ?>" placeholder="e.g. 145">
            </div>
        </div>

        <div class="pf-side-foot">
            <button type="submit" class="btn btn-success w-100 shadow rounded-pill"><i class="fe-check-circle me-1"></i> Update Product</button>
        </div>
    </div>
</div>
<?php /**PATH D:\projects\bilaiGhor\resources\views/backEnd/product/partials/edit_sidebar.blade.php ENDPATH**/ ?>