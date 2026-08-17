
<?php $__env->startSection('title', 'Release Update'); ?>

<?php $__env->startSection('content'); ?>

<style>
    .release-container {
        font-family: 'Inter', system-ui, -apple-system, sans-serif;
        padding-top: 20px;
        padding-bottom: 40px;
    }

    .card-modern {
        background: #ffffff;
        border-radius: 12px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        height: 100%;
    }

    .card-header-modern {
        padding: 20px 25px;
        border-bottom: 1px solid #f1f5f9;
        font-weight: 700;
        color: #1e293b;
        display: flex;
        align-items: center;
    }

    .card-body {
        padding: 25px;
    }

    .form-label-modern {
        font-size: 13px;
        font-weight: 600;
        color: #374151;
        margin-bottom: 8px;
        display: block;
    }

    .form-control-modern {
        border: 1px solid #d1d5db;
        border-radius: 8px;
        padding: 10px 12px;
        font-size: 14px;
        transition: all 0.2s;
    }

    .form-control-modern:focus {
        border-color: #4f46e5;
        box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        outline: none;
    }

    .table-modern {
        font-size: 14px;
    }

    .table-modern th {
        background: #f8fafc;
        font-weight: 600;
        color: #475569;
        border-bottom: 2px solid #e2e8f0;
        padding: 12px;
    }

    .table-modern td {
        padding: 12px;
        vertical-align: middle;
        border-bottom: 1px solid #f1f5f9;
    }

    .badge-status {
        padding: 6px 12px;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 600;
    }

    .badge-active {
        background: #dcfce7;
        color: #166534;
    }

    .badge-inactive {
        background: #fee2e2;
        color: #991b1b;
    }

    .btn-icon {
        width: 32px;
        height: 32px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 8px;
        transition: all 0.2s;
        border: none;
        cursor: pointer;
    }

    .btn-icon:hover {
        transform: translateY(-2px);
    }

    .btn-toggle {
        background: #e0e7ff;
        color: #4338ca;
    }

    .btn-delete {
        background: #fee2e2;
        color: #991b1b;
    }
</style>

<div class="content-wrapper">
    <div class="container-fluid release-container">
        
        <div class="d-flex align-items-center mb-4">
            <h3 class="m-0 fw-bold text-dark">
                <i class="fas fa-upload text-primary me-2"></i> Release Update
            </h3>
        </div>

        <div class="row g-4">
            
            
            <div class="col-lg-4">
                <div class="card card-modern h-100">
                    <div class="card-header-modern">
                        <i data-feather="upload" class="me-2" style="width:18px;"></i>
                        Release New Update
                    </div>
                    <div class="card-body">
                        <form action="<?php echo e(route('admin.update.release.store')); ?>" method="POST" enctype="multipart/form-data">
                            <?php echo csrf_field(); ?>

                            <div class="mb-3">
                                <label class="form-label-modern">Version Number <span class="text-danger">*</span></label>
                                <input type="text" name="version" 
                                       class="form-control form-control-modern <?php $__errorArgs = ['version'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" 
                                       value="<?php echo e(old('version')); ?>" 
                                       placeholder="e.g., 1.1.0" 
                                       pattern="^\d+\.\d+\.\d+$"
                                       required>
                                <small class="text-muted">Format: X.X.X (e.g., 1.1.0)</small>
                                <?php $__errorArgs = ['version'];
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

                            <div class="mb-3">
                                <label class="form-label-modern">Release Date <span class="text-danger">*</span></label>
                                <input type="date" name="release_date" 
                                       class="form-control form-control-modern <?php $__errorArgs = ['release_date'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" 
                                       value="<?php echo e(old('release_date', date('Y-m-d'))); ?>" 
                                       required>
                                <?php $__errorArgs = ['release_date'];
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

                            <div class="mb-3">
                                <label class="form-label-modern">Changelog</label>
                                <textarea name="changelog" 
                                          class="form-control form-control-modern <?php $__errorArgs = ['changelog'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" 
                                          rows="4" 
                                          placeholder="Describe what's new in this update..."><?php echo e(old('changelog')); ?></textarea>
                                <?php $__errorArgs = ['changelog'];
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

                            <div class="mb-3">
                                <label class="form-label-modern">Update File (ZIP) <span class="text-danger">*</span></label>
                                <input type="file" name="update_file" 
                                       class="form-control form-control-modern <?php $__errorArgs = ['update_file'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" 
                                       accept=".zip"
                                       required>
                                <small class="text-muted">Maximum file size: 100MB</small>
                                <?php $__errorArgs = ['update_file'];
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

                            <div class="mb-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="requires_migration" id="requires_migration" value="1">
                                    <label class="form-check-label" for="requires_migration">
                                        Requires Database Migration
                                    </label>
                                </div>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary py-2 fw-bold">
                                    <i class="fas fa-upload me-2"></i> Release Update
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            
            <div class="col-lg-8">
                <div class="card card-modern h-100">
                    <div class="card-header-modern d-flex justify-content-between align-items-center">
                        <span>Released Versions</span>
                        <span class="badge bg-light text-dark border"><?php echo e($versions->count()); ?> Versions</span>
                    </div>
                    
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-modern mb-0">
                                <thead>
                                    <tr>
                                        <th width="15%">Version</th>
                                        <th width="15%">Release Date</th>
                                        <th width="30%">Changelog</th>
                                        <th width="10%">File Size</th>
                                        <th width="10%">Status</th>
                                        <th width="20%" class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $__empty_1 = true; $__currentLoopData = $versions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $version): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                        <tr>
                                            <td>
                                                <span class="fw-bold text-primary"><?php echo e($version->version); ?></span>
                                                <?php if($version->requires_migration): ?>
                                                    <br><small class="text-warning"><i class="fas fa-database"></i> Migration</small>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo e($version->release_date->format('M d, Y')); ?></td>
                                            <td>
                                                <div class="text-truncate" style="max-width: 300px;" title="<?php echo e($version->changelog); ?>">
                                                    <?php echo e($version->changelog ?? 'No changelog'); ?>

                                                </div>
                                            </td>
                                            <td>
                                                <?php if($version->file_size): ?>
                                                    <?php echo e(number_format($version->file_size / 1024 / 1024, 2)); ?> MB
                                                <?php else: ?>
                                                    N/A
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if($version->is_active): ?>
                                                    <span class="badge-status badge-active">Active</span>
                                                <?php else: ?>
                                                    <span class="badge-status badge-inactive">Inactive</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-end">
                                                <form action="<?php echo e(route('admin.update.release.toggle', $version->id)); ?>" method="POST" class="d-inline">
                                                    <?php echo csrf_field(); ?>
                                                    <button type="submit" class="btn-icon btn-toggle" 
                                                            title="<?php echo e($version->is_active ? 'Deactivate' : 'Activate'); ?>">
                                                        <i class="fas fa-<?php echo e($version->is_active ? 'toggle-on' : 'toggle-off'); ?>"></i>
                                                    </button>
                                                </form>
                                                <form action="<?php echo e(route('admin.update.release.destroy', $version->id)); ?>" method="POST" class="d-inline"
                                                      onsubmit="return confirm('Are you sure you want to delete version <?php echo e($version->version); ?>? This will also delete the update file.');">
                                                    <?php echo csrf_field(); ?>
                                                    <?php echo method_field('DELETE'); ?>
                                                    <button type="submit" class="btn-icon btn-delete" title="Delete">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                        <tr>
                                            <td colspan="6" class="text-center text-muted py-4">
                                                <i class="fas fa-inbox fa-2x mb-2"></i>
                                                <p class="mb-0">No versions released yet</p>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('backEnd.layouts.master', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\bilaiGhor\resources\views\backEnd\update\release.blade.php ENDPATH**/ ?>