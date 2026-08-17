
<?php $__env->startSection('title','Mark Attendance'); ?>

<?php $__env->startSection('css'); ?>
<style>
    /* --- Card & Form Styles --- */
    .card-modern {
        border: none;
        border-radius: 16px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
        background: #fff;
    }
    .card-header-modern {
        background: #fff;
        border-bottom: 1px solid #f1f5f9;
        padding: 1.5rem;
        border-radius: 16px 16px 0 0 !important;
        display: flex; justify-content: space-between; align-items: center;
    }
    
    .form-label-custom {
        font-size: 0.85rem; font-weight: 600; color: #475569; margin-bottom: 0.5rem;
    }
    .form-control-custom, .form-select-custom {
        border: 1px solid #e2e8f0; border-radius: 10px;
        padding: 0.75rem 1rem; font-size: 0.95rem;
        transition: all 0.2s;
    }
    .form-control-custom:focus, .form-select-custom:focus {
        border-color: #6366f1; box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
    }

    /* --- Visual Status Radio Buttons --- */
    .status-group {
        display: flex; gap: 10px; flex-wrap: wrap;
    }
    .status-input { display: none; }
    .status-label {
        flex: 1; text-align: center;
        padding: 10px; border-radius: 8px; border: 1px solid #e2e8f0;
        cursor: pointer; font-size: 0.85rem; font-weight: 600; color: #64748b;
        transition: all 0.2s; background: #fff; min-width: 80px;
    }
    .status-label:hover { background: #f8fafc; }
    
    /* Checked States */
    .status-input:checked + .status-label {
        border-color: transparent; color: #fff; box-shadow: 0 4px 6px rgba(0,0,0,0.1); transform: translateY(-1px);
    }
    #st_present:checked + .status-label { background-color: #10b981; }
    #st_absent:checked + .status-label { background-color: #ef4444; }
    #st_late:checked + .status-label { background-color: #f59e0b; }
    #st_half:checked + .status-label { background-color: #3b82f6; }
    #st_holiday:checked + .status-label { background-color: #6366f1; }
</style>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<div class="container-fluid py-4">

    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            
            <form action="<?php echo e(route('admin.attendances.store')); ?>" method="POST">
                <?php echo csrf_field(); ?>

                <div class="card card-modern">
                    
                    
                    <div class="card-header-modern">
                        <div>
                            <h5 class="mb-1 fw-bold text-dark">Mark Attendance</h5>
                            <p class="text-muted small mb-0">Record daily attendance for an employee.</p>
                        </div>
                        <a href="<?php echo e(route('admin.attendances.index')); ?>" class="btn btn-light btn-sm rounded-pill px-3">
                            <i data-feather="list" style="width:14px;" class="me-1"></i> List
                        </a>
                    </div>

                    <div class="card-body p-4">
                        
                        
                        <div class="mb-4">
                            <label class="form-label-custom">Select Employee <span class="text-danger">*</span></label>
                            <select name="employee_id" class="form-control select2 form-select-custom <?php $__errorArgs = ['employee_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" required>
                                <option value="">-- Choose Employee --</option>
                                <?php $__currentLoopData = $employees; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $emp): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($emp->id); ?>" <?php echo e(old('employee_id') == $emp->id ? 'selected' : ''); ?>>
                                        <?php echo e($emp->name); ?> (ID: <?php echo e($emp->employee_id); ?>)
                                    </option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                            <?php $__errorArgs = ['employee_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="text-danger small"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>

                        
                        <div class="mb-4">
                            <label class="form-label-custom">Attendance Date <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-0 text-muted"><i data-feather="calendar" style="width:16px;"></i></span>
                                <input type="date" name="attendance_date" class="form-control form-control-custom border-start-0 <?php $__errorArgs = ['attendance_date'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" 
                                       value="<?php echo e(old('attendance_date', date('Y-m-d'))); ?>" required>
                            </div>
                            <?php $__errorArgs = ['attendance_date'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="text-danger small"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>

                        
                        <div class="row g-3 mb-4">
                            <div class="col-6">
                                <label class="form-label-custom">Check In</label>
                                <input type="time" name="check_in" class="form-control form-control-custom" value="<?php echo e(old('check_in')); ?>">
                            </div>
                            <div class="col-6">
                                <label class="form-label-custom">Check Out</label>
                                <input type="time" name="check_out" class="form-control form-control-custom" value="<?php echo e(old('check_out')); ?>">
                            </div>
                        </div>

                        
                        <div class="mb-4">
                            <label class="form-label-custom d-block mb-2">Status <span class="text-danger">*</span></label>
                            <div class="status-group">
                                <input type="radio" name="status" id="st_present" value="present" class="status-input" <?php echo e(old('status') == 'present' ? 'checked' : ''); ?>>
                                <label for="st_present" class="status-label">Present</label>

                                <input type="radio" name="status" id="st_late" value="late" class="status-input" <?php echo e(old('status') == 'late' ? 'checked' : ''); ?>>
                                <label for="st_late" class="status-label">Late</label>

                                <input type="radio" name="status" id="st_half" value="half_day" class="status-input" <?php echo e(old('status') == 'half_day' ? 'checked' : ''); ?>>
                                <label for="st_half" class="status-label">Half Day</label>

                                <input type="radio" name="status" id="st_absent" value="absent" class="status-input" <?php echo e(old('status') == 'absent' ? 'checked' : ''); ?>>
                                <label for="st_absent" class="status-label">Absent</label>

                                <input type="radio" name="status" id="st_holiday" value="holiday" class="status-input" <?php echo e(old('status') == 'holiday' ? 'checked' : ''); ?>>
                                <label for="st_holiday" class="status-label">Holiday</label>
                            </div>
                            <?php $__errorArgs = ['status'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="text-danger small mt-1 d-block"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>

                        
                        <div class="mb-4">
                            <label class="form-label-custom">Notes (Optional)</label>
                            <textarea name="notes" class="form-control form-control-custom" rows="2" placeholder="Any remarks regarding attendance..."><?php echo e(old('notes')); ?></textarea>
                        </div>

                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary py-2 fw-bold shadow-sm">
                                <i data-feather="check-circle" class="me-1" style="width: 16px;"></i> Submit Attendance
                            </button>
                            <a href="<?php echo e(route('admin.attendances.index')); ?>" class="btn btn-light py-2">Cancel</a>
                        </div>

                    </div>
                </div>
            </form>

        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
    $(document).ready(function() {
        if ($.fn.select2) {
            $('.select2').select2({ width: '100%' });
        }
    });
</script>
<?php $__env->stopPush(); ?>
<?php echo $__env->make('backEnd.layouts.master', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\bilaiGhor\resources\views\backEnd\attendances\create.blade.php ENDPATH**/ ?>