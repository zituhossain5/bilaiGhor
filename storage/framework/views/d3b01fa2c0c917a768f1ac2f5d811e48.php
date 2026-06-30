
<?php $__env->startSection('title','Edit Category'); ?>

<?php $__env->startSection('css'); ?>
<link href="<?php echo e(asset('public/backEnd')); ?>/assets/libs/select2/css/select2.min.css" rel="stylesheet" type="text/css" />
<link href="<?php echo e(asset('public/backEnd')); ?>/assets/libs/summernote/summernote-lite.min.css" rel="stylesheet" type="text/css" />

<style>
    /* Premium Card Design */
    .card {
        border: none;
        box-shadow: 0 0 20px rgba(18, 38, 63, 0.03);
        border-radius: 10px;
        background: #fff;
    }
    .card-header {
        background: #fff;
        border-bottom: 1px solid #f1f5f7;
        padding: 15px 20px;
    }
    .card-title {
        font-size: 16px;
        font-weight: 700;
        color: #343a40;
        margin: 0;
        display: flex;
        align-items: center;
    }
    .card-title i {
        margin-right: 8px;
        color: #727cf5;
        background: rgba(114, 124, 245, 0.1);
        padding: 6px;
        border-radius: 5px;
        font-size: 14px;
    }

    /* Form Inputs */
    .form-label {
        font-weight: 600;
        font-size: 13px;
        color: #6c757d;
        margin-bottom: 8px;
    }
    .form-control {
        border: 1px solid #eef2f7;
        padding: 10px 15px;
        border-radius: 6px;
        font-size: 14px;
        color: #313b5e;
        transition: all 0.3s;
    }
    .form-control:focus {
        border-color: #727cf5;
        box-shadow: 0 0 0 0.2rem rgba(114, 124, 245, 0.15);
    }

    /* Custom Image Upload Box */
    .image-upload-box {
        border: 2px dashed #eef2f7;
        border-radius: 10px;
        padding: 20px;
        text-align: center;
        cursor: pointer;
        position: relative;
        transition: 0.3s;
        background: #f9fbfd;
        min-height: 150px;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
    }
    .image-upload-box:hover {
        border-color: #727cf5;
        background: #fff;
    }
    .preview-img {
        max-width: 100%;
        max-height: 120px;
        border-radius: 6px;
        box-shadow: 0 4px 10px rgba(0,0,0,0.05);
    }
    .upload-placeholder {
        display: flex;
        flex-direction: column;
        align-items: center;
    }
    .upload-icon {
        font-size: 24px;
        color: #98a6ad;
        margin-bottom: 8px;
    }
    .upload-text {
        font-size: 12px;
        color: #98a6ad;
    }

    /* Toggle Switch */
    .switch { position: relative; display: inline-block; width: 42px; height: 22px; }
    .switch input { opacity: 0; width: 0; height: 0; }
    .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #eef2f7; transition: .4s; border-radius: 34px; border: 1px solid #dee2e6; }
    .slider:before { position: absolute; content: ""; height: 16px; width: 16px; left: 2px; bottom: 2px; background-color: white; transition: .4s; border-radius: 50%; box-shadow: 0 1px 2px rgba(0,0,0,0.2); }
    input:checked + .slider { background-color: #0acf97; border-color: #0acf97; }
    input:checked + .slider:before { transform: translateX(20px); }
</style>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<div class="container-fluid">
    
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between py-3">
                <h4 class="page-title mb-0 text-dark font-weight-bold">Edit Category: <?php echo e($edit_data->name); ?></h4>
                <div class="page-title-right">
                    <a href="<?php echo e(route('categories.index')); ?>" class="btn btn-light rounded-pill border shadow-sm">
                        <i class="fe-arrow-left me-1"></i> Back to List
                    </a>
                </div>
            </div>
        </div>
    </div>

    <form action="<?php echo e(route('categories.update')); ?>" method="POST" enctype="multipart/form-data" data-parsley-validate>
        <?php echo csrf_field(); ?>
        <input type="hidden" value="<?php echo e($edit_data->id); ?>" name="id">
        
        <div class="row">
            
            <div class="col-lg-8">
                
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title"><i class="fe-file-text"></i> General Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="form-group mb-3">
                            <label for="name" class="form-label">Category Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control <?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" 
                                   name="name" value="<?php echo e($edit_data->name); ?>" id="name" required>
                            <?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                <div class="invalid-feedback"><?php echo e($message); ?></div>
                            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title"><i class="fe-search"></i> SEO Configuration</h5>
                    </div>
                    <div class="card-body">
                        <div class="form-group mb-3">
                            <label for="meta_title" class="form-label">Meta Title</label>
                            <input type="text" class="form-control <?php $__errorArgs = ['meta_title'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" 
                                   name="meta_title" value="<?php echo e($edit_data->meta_title); ?>" id="meta_title">
                            <?php $__errorArgs = ['meta_title'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                <div class="invalid-feedback"><?php echo e($message); ?></div>
                            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>

                        <div class="form-group mb-0">
                            <label for="meta_description" class="form-label">Meta Description</label>
                            <textarea class="summernote form-control <?php $__errorArgs = ['meta_description'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" 
                                      name="meta_description" id="meta_description"><?php echo $edit_data->meta_description; ?></textarea>
                            <?php $__errorArgs = ['meta_description'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                <div class="invalid-feedback"><?php echo e($message); ?></div>
                            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title"><i class="fe-settings"></i> Visibility</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3 p-2 bg-light rounded">
                            <div>
                                <label class="form-label mb-0">Publish Status</label>
                                <small class="d-block text-muted">Show in store</small>
                            </div>
                            <label class="switch">
                                <input type="checkbox" name="status" value="1" <?php echo e($edit_data->status == 1 ? 'checked' : ''); ?>>
                                <span class="slider round"></span>
                            </label>
                        </div>

                        <div class="d-flex justify-content-between align-items-center p-2 bg-light rounded">
                            <div>
                                <label class="form-label mb-0">Front View</label>
                                <small class="d-block text-muted">Show on homepage</small>
                            </div>
                            <label class="switch">
                                <input type="checkbox" name="front_view" value="1" <?php echo e($edit_data->front_view == 1 ? 'checked' : ''); ?>>
                                <span class="slider round"></span>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title"><i class="fe-image"></i> Media</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-4">
                            <label class="form-label">Main Image</label>
                            <div class="image-upload-box" onclick="document.getElementById('image').click()">
                                <input type="file" name="image" id="image" class="d-none" onchange="readURL(this, 'preview_main', 'placeholder_main')">
                                
                                <img id="preview_main" class="preview-img" src="<?php echo e(asset($edit_data->image)); ?>" 
                                     style="<?php echo e($edit_data->image ? 'display:block;' : 'display:none;'); ?>">
                                
                                <div id="placeholder_main" class="upload-placeholder" 
                                     style="<?php echo e($edit_data->image ? 'display:none;' : 'display:flex;'); ?>">
                                    <i class="fe-upload-cloud upload-icon"></i>
                                    <p class="upload-text mb-0">Click to change image</p>
                                </div>
                            </div>
                            <?php $__errorArgs = ['image'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                <span class="text-danger small"><?php echo e($message); ?></span>
                            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>

                        <div class="mb-0">
                            <label class="form-label">Category Icon</label>
                            <div class="image-upload-box" onclick="document.getElementById('icon').click()">
                                <input type="file" name="icon" id="icon" class="d-none" onchange="readURL(this, 'preview_icon', 'placeholder_icon')">
                                
                                <img id="preview_icon" class="preview-img" src="<?php echo e(asset($edit_data->icon)); ?>" 
                                     style="<?php echo e($edit_data->icon ? 'display:block; max-height:60px;' : 'display:none;'); ?>">
                                
                                <div id="placeholder_icon" class="upload-placeholder" 
                                     style="<?php echo e($edit_data->icon ? 'display:none;' : 'display:flex;'); ?>">
                                    <i class="fe-image upload-icon"></i>
                                    <p class="upload-text mb-0">Click to change icon</p>
                                </div>
                            </div>
                            <?php $__errorArgs = ['icon'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                <span class="text-danger small"><?php echo e($message); ?></span>
                            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-success w-100 rounded-pill shadow-lg py-2 fw-bold">
                    <i class="fe-check-circle me-1"></i> Update Category
                </button>

            </div>
        </div>
    </form>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('script'); ?>
<script src="<?php echo e(asset('public/backEnd/')); ?>/assets/libs/parsleyjs/parsley.min.js"></script>
<script src="<?php echo e(asset('public/backEnd/')); ?>/assets/js/pages/form-validation.init.js"></script>
<script src="<?php echo e(asset('public/backEnd/')); ?>/assets/libs/select2/js/select2.min.js"></script>
<script src="<?php echo e(asset('public/backEnd/')); ?>/assets/libs/summernote/summernote-lite.min.js"></script>

<script>
    $(document).ready(function(){
        // Initialize Summernote
        $(".summernote").summernote({
            placeholder: "Enter SEO description...",
            height: 120,
            toolbar: [
                ['style', ['bold', 'italic', 'underline', 'clear']],
                ['para', ['ul', 'ol']],
                ['misc', ['fullscreen', 'codeview']]
            ]
        });
        
        $(".select2").select2();
    });

    // Smart Image Preview Function
    function readURL(input, previewId, placeholderId) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) {
                $('#' + previewId).attr('src', e.target.result).show();
                $('#' + placeholderId).hide();
            }
            reader.readAsDataURL(input.files[0]);
        }
    }
</script>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('backEnd.layouts.master', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\projects\bilaiGhor\resources\views/backEnd/category/edit.blade.php ENDPATH**/ ?>