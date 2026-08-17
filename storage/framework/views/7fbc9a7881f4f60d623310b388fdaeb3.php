
<?php $__env->startSection('title', 'Purchase Report'); ?>

<?php $__env->startSection('css'); ?>
<style>
    /* --- Modern Card --- */
    .card-modern {
        border: none;
        border-radius: 12px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
        background: #fff;
        margin-bottom: 24px;
        transition: transform 0.2s;
    }
    
    /* --- Filter Section --- */
    .filter-card {
        background: #fff;
        border-radius: 12px;
        padding: 20px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.02);
        border: 1px solid #e2e8f0;
        margin-bottom: 24px;
    }
    .form-label-custom {
        font-size: 0.8rem; font-weight: 600; text-transform: uppercase; color: #64748b; margin-bottom: 5px;
    }
    .form-control-custom, .form-select-custom {
        border: 1px solid #e2e8f0; border-radius: 8px; padding: 10px 15px; font-size: 0.9rem;
    }
    .form-control-custom:focus, .form-select-custom:focus {
        border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
    }

    /* --- Stat Cards --- */
    .stat-card {
        display: flex; align-items: center; padding: 20px;
        background: #fff; border-radius: 12px; border: 1px solid #f1f5f9;
        box-shadow: 0 2px 6px rgba(0,0,0,0.02);
    }
    .stat-icon {
        width: 50px; height: 50px; border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        font-size: 24px; margin-right: 15px;
    }
    .bg-light-primary { background: #e0e7ff; color: #4338ca; }
    .bg-light-success { background: #dcfce7; color: #166534; }
    .bg-light-warning { background: #fef3c7; color: #b45309; }
    
    .stat-label { font-size: 0.85rem; color: #64748b; font-weight: 500; margin-bottom: 2px; }
    .stat-value { font-size: 1.5rem; font-weight: 700; color: #1e293b; margin: 0; }

    /* --- Table Styles --- */
    .table-modern th {
        background-color: #f8fafc; color: #475569; font-size: 0.75rem;
        font-weight: 700; text-transform: uppercase; padding: 1rem; border-bottom: 1px solid #e2e8f0;
    }
    .table-modern td {
        padding: 1rem; vertical-align: middle; font-size: 0.875rem; color: #334155;
        border-bottom: 1px solid #f1f5f9;
    }
    .table-modern tr:hover td { background-color: #f8fafc; }

    /* --- Utilities --- */
    .btn-custom-primary { background: #4f46e5; color: #fff; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 600; }
    .btn-custom-primary:hover { background: #4338ca; color: #fff; }
    .btn-custom-outline { background: transparent; border: 1px solid #10b981; color: #10b981; padding: 10px 20px; border-radius: 8px; font-weight: 600; }
    .btn-custom-outline:hover { background: #10b981; color: #fff; }
</style>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<div class="container-fluid py-4">

    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1 fw-bold text-dark">
                <i data-feather="shopping-cart" class="text-primary me-2"></i> Purchase Reports
            </h4>
            <p class="text-muted small mb-0">Analysis for: <strong><?php echo e($label ?? 'Today'); ?></strong></p>
        </div>
    </div>

    
    <div class="filter-card">
        <form method="GET" action="<?php echo e(route('admin.reports.purchases')); ?>" id="purchase-filter-form">
            <div class="row g-3 align-items-end">
                
                
                <div class="col-md-3">
                    <label class="form-label-custom">Filter By</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0"><i data-feather="filter" style="width:16px;"></i></span>
                        <select name="type" class="form-select form-select-custom border-start-0" id="report-type">
                            <option value="today" <?php echo e(($type ?? '')=='today' ? 'selected' : ''); ?>>Today</option>
                            <option value="month" <?php echo e(($type ?? '')=='month' ? 'selected' : ''); ?>>Monthly</option>
                            <option value="year"  <?php echo e(($type ?? '')=='year'  ? 'selected' : ''); ?>>Yearly</option>
                            <option value="range" <?php echo e(($type ?? '')=='range' ? 'selected' : ''); ?>>Custom Date Range</option>
                        </select>
                    </div>
                </div>

                
                <div class="col-md-2 type-month type-year" style="display:none;">
                    <label class="form-label-custom">Year</label>
                    <input type="number" name="year" class="form-control form-control-custom" value="<?php echo e(request('year', now()->year)); ?>" placeholder="YYYY">
                </div>

                <div class="col-md-2 type-month" style="display:none;">
                    <label class="form-label-custom">Month</label>
                    <select name="month" class="form-select form-select-custom">
                        <?php for($m=1; $m<=12; $m++): ?>
                            <option value="<?php echo e($m); ?>" <?php echo e(request('month', now()->month) == $m ? 'selected' : ''); ?>>
                                <?php echo e(date('F', mktime(0, 0, 0, $m, 1))); ?>

                            </option>
                        <?php endfor; ?>
                    </select>
                </div>

                <div class="col-md-2 type-range" style="display:none;">
                    <label class="form-label-custom">Start Date</label>
                    <input type="date" name="from_date" class="form-control form-control-custom" value="<?php echo e(request('from_date')); ?>">
                </div>

                <div class="col-md-2 type-range" style="display:none;">
                    <label class="form-label-custom">End Date</label>
                    <input type="date" name="to_date" class="form-control form-control-custom" value="<?php echo e(request('to_date')); ?>">
                </div>

                
                <div class="col-md-auto ms-auto d-flex gap-2">
                    <button class="btn btn-custom-primary" type="submit">
                        <i data-feather="search" class="me-1" style="width:16px;"></i> Generate
                    </button>
                    <button class="btn btn-custom-outline" type="submit" name="export" value="csv" id="export-csv-btn">
                        <i data-feather="download" class="me-1" style="width:16px;"></i> CSV
                    </button>
                </div>
            </div>
        </form>
    </div>

    
    <div class="row g-4 mb-4">
        
        <div class="col-md-4">
            <div class="stat-card">
                <div class="stat-icon bg-light-primary">
                    <i data-feather="shopping-bag"></i>
                </div>
                <div>
                    <div class="stat-label">Total Purchase</div>
                    <h3 class="stat-value">৳<?php echo e(number_format($totalPurchaseAmount ?? 0, 2)); ?></h3>
                </div>
            </div>
        </div>

        
        <div class="col-md-4">
            <div class="stat-card">
                <div class="stat-icon bg-light-success">
                    <i data-feather="check-circle"></i>
                </div>
                <div>
                    <div class="stat-label">Paid Amount</div>
                    <h3 class="stat-value">৳<?php echo e(number_format($totalPaid ?? 0, 2)); ?></h3>
                </div>
            </div>
        </div>

        
        <div class="col-md-4">
            <div class="stat-card">
                <div class="stat-icon bg-light-warning">
                    <i data-feather="alert-circle"></i>
                </div>
                <div>
                    <div class="stat-label">Total Due</div>
                    <h3 class="stat-value">৳<?php echo e(number_format($totalDue ?? 0, 2)); ?></h3>
                </div>
            </div>
        </div>
    </div>

    
    <div id="purchase-table-wrapper">
        <div class="card card-modern">
            <div class="card-header border-bottom bg-white py-3">
                <h5 class="mb-0 fw-bold text-dark">Detailed Purchase List</h5>
            </div>
            
            <div class="table-responsive">
                <table class="table table-modern mb-0">
                    <thead>
                        <tr>
                            <th width="5%">#</th>
                            <th width="15%">Invoice</th>
                            <th width="20%">Supplier</th>
                            <th width="15%" class="text-end">Total Amount</th>
                            <th width="15%" class="text-end">Paid</th>
                            <th width="15%" class="text-end">Due</th>
                            <th width="15%">Date</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $purchases; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr>
                            <td class="text-muted"><?php echo e($loop->iteration); ?></td>
                            <td><span class="fw-bold text-primary">#<?php echo e($p->invoice_no ?? $p->id); ?></span></td>
                            <td class="fw-medium text-dark"><?php echo e($p->supplier->name ?? '-'); ?></td>
                            <td class="text-end fw-bold text-dark">৳<?php echo e(number_format($p->grand_total ?? 0, 2)); ?></td>
                            <td class="text-end text-success">৳<?php echo e(number_format($p->paid_amount ?? 0, 2)); ?></td>
                            <td class="text-end <?php echo e(($p->due_amount ?? 0) > 0 ? 'text-danger fw-bold' : 'text-muted'); ?>">
                                ৳<?php echo e(number_format($p->due_amount ?? 0, 2)); ?>

                            </td>
                            <td class="text-muted small"><?php echo e(optional($p->purchase_date)->format('d M, Y')); ?></td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr>
                            <td colspan="7" class="text-center py-5">
                                <div class="d-flex flex-column align-items-center">
                                    <img src="https://cdn-icons-png.flaticon.com/512/7486/7486744.png" width="50" class="opacity-25 mb-2">
                                    <p class="text-muted fw-bold mb-0">No purchases found</p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>

            
            <div class="p-4 border-top d-flex justify-content-between align-items-center">
                <small class="text-muted">Showing <?php echo e($purchases->firstItem()); ?> to <?php echo e($purchases->lastItem()); ?> of <?php echo e($purchases->total()); ?> entries</small>
                <div><?php echo e($purchases->links('pagination::bootstrap-4')); ?></div>
            </div>
        </div>
    </div>

</div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('script'); ?>
<script>
    /* -------- Toggle fields -------- */
    function toggleReportFields() {
        let type = document.getElementById('report-type').value;

        document.querySelectorAll('.type-month,.type-year,.type-range')
            .forEach(el => el.style.display = 'none');

        if (type === 'month') {
            document.querySelectorAll('.type-month,.type-year')
                .forEach(el => el.style.display = 'block');
        } else if (type === 'year') {
            document.querySelectorAll('.type-year')
                .forEach(el => el.style.display = 'block');
        } else if (type === 'range') {
            document.querySelectorAll('.type-range')
                .forEach(el => el.style.display = 'block');
        }
    }

    document.getElementById('report-type')
        .addEventListener('change', toggleReportFields);
    toggleReportFields();

    /* -------- AJAX Filter + Pagination -------- */
    const form = document.getElementById('purchase-filter-form');

    form.addEventListener('submit', function(e){
        // CSV হলে normal submit
        if (e.submitter && e.submitter.id === 'export-csv-btn') {
            return true;
        }

        e.preventDefault();
        loadPurchases(new URLSearchParams(new FormData(form)).toString());
    });

    document.addEventListener('click', function(e){
        let link = e.target.closest('.pagination a');
        if (!link) return;

        e.preventDefault();
        let query = link.getAttribute('href').split('?')[1] || '';
        loadPurchases(query);
    });

    function loadPurchases(query) {
        fetch("<?php echo e(route('admin.reports.purchases')); ?>?" + query, {
            headers: {'X-Requested-With':'XMLHttpRequest'}
        })
        .then(res => res.text())
        .then(html => {
            let temp = document.createElement('div');
            temp.innerHTML = html;

            document.getElementById('purchase-table-wrapper').innerHTML =
                temp.querySelector('#purchase-table-wrapper').innerHTML;
        });
    }
</script>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('backEnd.layouts.master', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\bilaiGhor\resources\views\backEnd\reports\purchases.blade.php ENDPATH**/ ?>