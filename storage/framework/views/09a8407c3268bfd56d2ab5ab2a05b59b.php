
<?php $__env->startSection('title', 'Laravel Error Log'); ?>

<?php $__env->startSection('content'); ?>
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between py-3">
                <h4 class="page-title mb-0">Laravel Error Log</h4>
                <div class="page-title-right">
                    <form action="<?php echo e(route('error-log.test')); ?>" method="POST" class="d-inline">
                        <?php echo csrf_field(); ?>
                        <button type="submit" class="btn btn-warning btn-sm rounded-pill me-1">
                            <i class="fe-file-text me-1"></i> টেস্ট লগ লিখুন
                        </button>
                    </form>
                    <form action="<?php echo e(route('error-log.create')); ?>" method="POST" class="d-inline">
                        <?php echo csrf_field(); ?>
                        <button type="submit" class="btn btn-success btn-sm rounded-pill me-1">
                            <i class="fe-plus me-1"></i> লগ ফাইল তৈরি করুন
                        </button>
                    </form>
                    <a href="<?php echo e(route('error-log.index')); ?>" class="btn btn-primary btn-sm rounded-pill">
                        <i class="fe-refresh-cw me-1"></i> রিফ্রেশ
                    </a>
                </div>
            </div>
        </div>
    </div>

    <?php if(session('success')): ?>
        <div class="alert alert-success"><?php echo e(session('success')); ?></div>
    <?php endif; ?>
    <?php if(session('error')): ?>
        <div class="alert alert-danger"><?php echo e(session('error')); ?></div>
    <?php endif; ?>

    <?php if($message ?? ''): ?>
        <div class="alert alert-info"><?php echo e($message); ?></div>
    <?php endif; ?>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <?php if($exists): ?>
                        <p class="text-muted small mb-2">
                            <strong>ফাইল:</strong> <code><?php echo e($path); ?></code>
                            <span class="ms-3">(শেষ ৫০০ লাইন)</span>
                            <?php if($writable ?? false): ?>
                                <span class="badge bg-success ms-2">লিখার পারমিশন আছে</span>
                            <?php else: ?>
                                <span class="badge bg-danger ms-2">লিখার পারমিশন নেই</span>
                            <?php endif; ?>
                            <?php if(isset($logChannel)): ?>
                                <span class="badge bg-secondary ms-1">Channel: <?php echo e($logChannel); ?></span>
                                <span class="badge bg-secondary ms-1">Level: <?php echo e($logLevel ?? 'debug'); ?></span>
                            <?php endif; ?>
                            <?php if(isset($configCached) && $configCached): ?>
                                <span class="badge bg-warning text-dark ms-1">Config cached</span>
                            <?php endif; ?>
                        </p>
                        <?php if(isset($configCached) && $configCached): ?>
                            <div class="alert alert-warning py-2 small mb-2">
                                Config ক্যাশ করা আছে। লগ না দেখা গেলে <code>php artisan config:clear</code> চালান।
                            </div>
                        <?php endif; ?>
                        <pre class="bg-dark text-light p-3 rounded" style="max-height:70vh;overflow:auto;font-size:12px;white-space:pre-wrap;word-wrap:break-word;"><?php echo e($content ?: 'লগ ফাইল খালি'); ?></pre>
                    <?php else: ?>
                        <div class="alert alert-warning">
                            <i class="fe-alert-triangle me-2"></i>
                            লগ ফাইল পাওয়া যায়নি: <code><?php echo e($path); ?></code>
                            <p class="mt-2 mb-0">উপরের <strong>"লগ ফাইল তৈরি করুন"</strong> বাটনে ক্লিক করুন।</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('backEnd.layouts.master', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\bilaiGhor\resources\views/backEnd/error-log/index.blade.php ENDPATH**/ ?>