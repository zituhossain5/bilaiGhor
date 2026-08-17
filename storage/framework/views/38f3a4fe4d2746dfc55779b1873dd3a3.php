<?php if($customers && count($customers) > 0): ?>
    <div class="card border-0 shadow-none mb-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-centered table-nowrap mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="border-top-0">Customer Name</th>
                            <th class="border-top-0 text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__currentLoopData = $customers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <tr>
                            <td class="align-middle">
                                <div class="d-flex align-items-center">
                                    <div class="avatar-xs bg-soft-primary rounded-circle me-2 d-flex align-items-center justify-content-center">
                                        <i class="fe-user text-primary font-size-13"></i>
                                    </div>
                                    <div>
                                        <h5 class="my-0 fw-semibold font-size-14">
                                            <a href="<?php echo e(route('customers.profile',['id'=>$value->id])); ?>" class="text-dark text-decoration-none">
                                                <?php echo e($value->name); ?>

                                            </a>
                                        </h5>
                                    </div>
                                </div>
                            </td>
                            <td class="text-end align-middle">
                                <a href="<?php echo e(route('customers.profile',['id'=>$value->id])); ?>" class="btn btn-xs btn-light rounded-circle shadow-sm">
                                    <i class="fe-arrow-right text-muted"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php else: ?>
    <div class="text-center p-3">
        <p class="text-muted mb-0"><i class="fe-alert-circle me-1"></i> No customers found.</p>
    </div>
<?php endif; ?><?php /**PATH C:\laragon\www\bilaiGhor\resources\views\backEnd\customer\search.blade.php ENDPATH**/ ?>