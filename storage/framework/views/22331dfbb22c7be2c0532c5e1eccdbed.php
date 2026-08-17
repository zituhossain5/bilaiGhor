<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <title><?php echo $__env->yieldContent('title', 'প্রো রিসেলার ড্যাশবোর্ড'); ?> - <?php echo e($user->shop_name ?? $user->name); ?></title>
    
    <!-- App favicon -->
    <?php if(isset($generalsetting) && !empty($generalsetting->favicon)): ?>
    <link rel="shortcut icon" href="<?php echo e(asset($generalsetting->favicon)); ?>" type="image/x-icon">
    <link rel="icon" href="<?php echo e(asset($generalsetting->favicon)); ?>" type="image/x-icon">
    <?php else: ?>
    <link rel="shortcut icon" href="<?php echo e(asset('public/backEnd/assets/images/favicon.ico')); ?>" type="image/x-icon">
    <link rel="icon" href="<?php echo e(asset('public/backEnd/assets/images/favicon.ico')); ?>" type="image/x-icon">
    <?php endif; ?>
    
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <style>
        :root {
            --primary: #4f46e5;
            --primary-dark: #4338ca;
            --secondary: #64748b;
            --success: #10b981;
            --bg-body: #f1f5f9;
            --sidebar-width: 280px;
            --card-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05), 0 4px 6px -2px rgba(0, 0, 0, 0.025);
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--bg-body);
            color: #0f172a;
            overflow-x: hidden;
        }

        /* --- Sidebar Styling --- */
        .sidebar {
            width: var(--sidebar-width);
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            background: #ffffff;
            border-right: 1px solid #e2e8f0;
            z-index: 1050;
            transition: transform 0.3s ease-in-out;
            padding: 20px 0;
        }

        .brand-logo {
            padding: 0 24px 20px;
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--primary);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            border-bottom: 1px solid #f1f5f9;
            min-height: 60px;
        }
        
        .brand-logo-wrapper {
            flex: 1;
            display: flex;
            align-items: center;
        }
        
        .brand-logo-link {
            display: flex;
            align-items: center;
            text-decoration: none;
            width: 100%;
            justify-content: flex-start;
        }
        
        .brand-logo-img {
            max-height: 50px;
            max-width: 100%;
            width: auto;
            height: auto;
            object-fit: contain;
            display: block;
        }
        
        .brand-menu-dropdown {
            position: relative;
        }
        
        .brand-menu-btn {
            background: transparent;
            border: none;
            color: var(--secondary);
            padding: 8px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
        }
        
        .brand-menu-btn:hover {
            background: #f1f5f9;
            color: var(--primary);
        }
        
        .brand-menu-btn:focus {
            box-shadow: none;
            outline: none;
        }
        
        .brand-menu-dropdown-menu {
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            padding: 8px;
            margin-top: 8px;
            min-width: 220px;
            max-height: 80vh;
            overflow-y: auto;
        }
        
        .brand-menu-dropdown-menu .dropdown-header {
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            color: #64748b;
            padding: 8px 12px;
            margin-bottom: 4px;
            letter-spacing: 0.5px;
        }
        
        .brand-menu-dropdown-menu .dropdown-item {
            border-radius: 8px;
            padding: 10px 12px;
            font-size: 0.9rem;
            transition: all 0.2s;
            display: flex;
            align-items: center;
        }
        
        .brand-menu-dropdown-menu .dropdown-item:hover {
            background: #f8fafc;
            color: var(--primary);
        }
        
        .brand-menu-dropdown-menu .dropdown-item i {
            width: 20px;
            text-align: center;
            color: inherit;
        }
        
        .brand-menu-dropdown-menu .dropdown-item.text-danger:hover {
            background: #fef2f2;
            color: #dc2626;
        }
        
        .brand-menu-dropdown-menu .dropdown-divider {
            margin: 6px 0;
            border-color: #e2e8f0;
        }
            width: 100%;
        }
        
        .brand-logo-img {
            max-height: 45px;
            width: auto;
            height: auto;
            object-fit: contain;
            display: block;
        }

        .menu-label {
            font-size: 0.75rem;
            text-transform: uppercase;
            color: #94a3b8;
            font-weight: 700;
            padding: 20px 24px 10px;
            letter-spacing: 0.5px;
        }

        .nav-item {
            padding: 0 12px;
        }
        
        .nav-link {
            display: flex;
            align-items: center;
            padding: 12px 16px;
            color: var(--secondary);
            font-weight: 500;
            border-radius: 8px;
            transition: all 0.2s;
            margin-bottom: 2px;
            text-decoration: none;
        }

        .nav-link i { width: 24px; font-size: 1.1rem; margin-right: 8px; }
        
        .nav-link:hover, .nav-link.active {
            background-color: #eef2ff;
            color: var(--primary);
        }

        /* --- Main Content --- */
        .main-content {
            margin-left: var(--sidebar-width);
            transition: margin-left 0.3s ease-in-out;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* --- Header --- */
        .top-header {
            background: #ffffff;
            padding: 15px 30px;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .mobile-toggle { 
            display: none; 
            font-size: 1.5rem; 
            cursor: pointer; 
            color: var(--secondary); 
            border: none; 
            background: none;
        }

        .wallet-badge {
            background: #f0fdf4;
            color: #15803d;
            border: 1px solid #bbf7d0;
            padding: 6px 16px;
            border-radius: 30px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* Notification Dropdown */
        .dropdown-menu {
            border-radius: 12px;
            border: 1px solid #e2e8f0;
        }
        
        .dropdown-item:hover {
            background-color: #f8f9fa;
        }
        
        .cursor-pointer {
            cursor: pointer;
        }

        /* --- Responsive Styles --- */
        @media (max-width: 991.98px) {
            .sidebar {
                transform: translateX(-100%);
            }
            .sidebar.show {
                transform: translateX(0);
            }
            .main-content {
                margin-left: 0;
            }
            .mobile-toggle {
                display: block;
                margin-right: 15px;
            }
            .sidebar-overlay {
                display: none;
                position: fixed;
                top: 0; left: 0; right: 0; bottom: 0;
                background: rgba(0,0,0,0.5);
                z-index: 1040;
            }
            .sidebar-overlay.show { display: block; }
        }
    </style>
    <?php echo $__env->yieldPushContent('styles'); ?>
</head>
<body>

    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <?php echo $__env->make('reseller.partials.sidebar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <main class="main-content">
        
        <header class="top-header">
            <div class="d-flex align-items-center">
                <button class="mobile-toggle" id="sidebarToggle">
                    <i class="fas fa-bars"></i>
                </button>
                <div>
                    <h5 class="fw-bold m-0 d-none d-md-block"><?php echo $__env->yieldContent('page-title', 'Dashboard'); ?></h5>
                    <small class="text-muted d-none d-md-block">Welcome back, <?php echo e($user->shop_name ?? $user->name); ?>!</small>
                </div>
            </div>
            
            <div class="d-flex align-items-center gap-3">
                <?php if(isset($demoMode) && $demoMode): ?>
                <span class="badge bg-warning text-dark px-2 py-1" title="ডেমো মুড সক্রিয়"><i class="fas fa-eye me-1"></i>ডেমো</span>
                <?php endif; ?>
                <div class="wallet-badge d-none d-sm-flex">
                    <i class="fas fa-wallet"></i> ৳ <?php echo e(number_format($user->wallet_balance ?? 0, 0)); ?>

                </div>

                <div class="dropdown position-relative">
                    <a href="#" class="position-relative text-decoration-none mx-2" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="far fa-bell fa-lg text-secondary"></i>
                        <?php if(isset($resellerTotalNotifications) && $resellerTotalNotifications > 0): ?>
                        <span class="position-absolute top-0 start-100 translate-middle p-1 bg-danger rounded-circle border border-light">
                            <small class="text-white fw-bold" style="font-size: 0.65rem;"><?php echo e($resellerTotalNotifications > 9 ? '9+' : $resellerTotalNotifications); ?></small>
                        </span>
                        <?php endif; ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 mt-3" style="width: 350px; max-height: 500px; overflow-y: auto;">
                        <li class="px-3 py-2 border-bottom bg-light">
                            <h6 class="mb-0 fw-bold text-dark">
                                <i class="fas fa-bell text-primary me-2"></i>নোটিফিকেশন
                                <?php if(isset($resellerTotalNotifications) && $resellerTotalNotifications > 0): ?>
                                <span class="badge bg-danger ms-2"><?php echo e($resellerTotalNotifications); ?></span>
                                <?php endif; ?>
                            </h6>
                        </li>
                        
                        <!-- Account Verification Notification -->
                        <?php if(isset($resellerVerificationStatus) && $resellerVerificationStatus != 'approved'): ?>
                        <li>
                            <a class="dropdown-item py-3 border-bottom" href="<?php echo e(route('reseller.verification.index')); ?>">
                                <div class="d-flex align-items-center">
                                    <div class="flex-shrink-0">
                                        <div class="rounded-circle bg-warning bg-opacity-10 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                            <i class="fas fa-exclamation-triangle text-warning"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <p class="mb-0 fw-semibold">
                                            <?php if($resellerVerificationStatus == 'pending'): ?>
                                                একাউন্ট ভেরিফিকেশন পেন্ডিং
                                            <?php elseif($resellerVerificationStatus == 'rejected'): ?>
                                                একাউন্ট ভেরিফিকেশন রিজেক্ট
                                            <?php else: ?>
                                                একাউন্ট ভেরিফাই করুন
                                            <?php endif; ?>
                                        </p>
                                        <small class="text-muted">আপনার একাউন্ট verify করা আবশ্যক</small>
                                    </div>
                                </div>
                            </a>
                        </li>
                        <?php endif; ?>
                        
                        <!-- Pending Orders Notification -->
                        <?php if(isset($resellerPendingOrders) && $resellerPendingOrders > 0): ?>
                        <li>
                            <a class="dropdown-item py-3 border-bottom" href="<?php echo e(route('reseller.orders')); ?>">
                                <div class="d-flex align-items-center">
                                    <div class="flex-shrink-0">
                                        <div class="rounded-circle bg-info bg-opacity-10 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                            <i class="fas fa-shopping-cart text-info"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <p class="mb-0 fw-semibold">নতুন অর্ডার</p>
                                        <small class="text-muted"><?php echo e($resellerPendingOrders); ?> টি পেন্ডিং অর্ডার</small>
                                    </div>
                                    <div class="flex-shrink-0">
                                        <span class="badge bg-danger rounded-pill"><?php echo e($resellerPendingOrders); ?></span>
                                    </div>
                                </div>
                            </a>
                        </li>
                        <?php endif; ?>
                        
                        <!-- Recent Orders -->
                        <?php if(isset($resellerRecentOrders) && $resellerRecentOrders->count() > 0): ?>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <div class="px-3 py-2 border-bottom">
                                <h6 class="mb-1 fw-bold text-dark small">সাম্প্রতিক অর্ডার</h6>
                            </div>
                        </li>
                        <?php $__currentLoopData = $resellerRecentOrders->take(3); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $order): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <li>
                            <a class="dropdown-item py-2" href="<?php echo e(route('reseller.orders')); ?>">
                                <div class="d-flex align-items-center">
                                    <div class="flex-shrink-0">
                                        <?php if($order->orderdetails->first() && $order->orderdetails->first()->product && $order->orderdetails->first()->product->image): ?>
                                            <img src="<?php echo e(asset($order->orderdetails->first()->product->image->image)); ?>" class="rounded" width="35" height="35" style="object-fit:cover;">
                <?php else: ?>
                                            <div class="rounded bg-light d-flex align-items-center justify-content-center" style="width:35px;height:35px;">
                                                <i class="fas fa-box text-muted"></i>
                    </div>
                <?php endif; ?>
                                    </div>
                                    <div class="flex-grow-1 ms-2">
                                        <p class="mb-0 small fw-semibold">#<?php echo e($order->invoice_id ?? $order->id); ?></p>
                                        <small class="text-muted"><?php echo e($order->customer->name ?? 'Guest'); ?></small>
                                    </div>
                                    <div class="flex-shrink-0 text-end">
                                        <span class="badge bg-success bg-opacity-10 text-success small">+৳<?php echo e(number_format($order->reseller_profit ?? 0, 0)); ?></span>
                                    </div>
                                </div>
                            </a>
                        </li>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        <?php endif; ?>
                        
                        <!-- Pending Withdrawals Notification -->
                        <?php if(isset($resellerPendingWithdrawals) && $resellerPendingWithdrawals > 0): ?>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item py-3 border-bottom" href="<?php echo e(route('reseller.withdrawals.index')); ?>">
                                <div class="d-flex align-items-center">
                                    <div class="flex-shrink-0">
                                        <div class="rounded-circle bg-warning bg-opacity-10 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                            <i class="fas fa-money-bill-wave text-warning"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <p class="mb-0 fw-semibold">পেন্ডিং উইথড্র</p>
                                        <small class="text-muted"><?php echo e($resellerPendingWithdrawals); ?> টি রিকোয়েস্ট প্রসেসিং</small>
                                    </div>
                                    <div class="flex-shrink-0">
                                        <span class="badge bg-warning rounded-pill"><?php echo e($resellerPendingWithdrawals); ?></span>
                                    </div>
                                </div>
                            </a>
                        </li>
                        <?php endif; ?>
                        
                        <!-- Recent Withdrawals -->
                        <?php if(isset($resellerRecentWithdrawals) && $resellerRecentWithdrawals->count() > 0): ?>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <div class="px-3 py-2 border-bottom">
                                <h6 class="mb-1 fw-bold text-dark small">সাম্প্রতিক উইথড্র</h6>
                            </div>
                        </li>
                        <?php $__currentLoopData = $resellerRecentWithdrawals; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $withdrawal): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <li>
                            <a class="dropdown-item py-2" href="<?php echo e(route('reseller.withdrawals.index')); ?>">
                                <div class="d-flex align-items-center">
                                    <div class="flex-shrink-0">
                                        <div class="rounded-circle bg-primary bg-opacity-10 d-flex align-items-center justify-content-center" style="width: 35px; height: 35px;">
                                            <i class="fas fa-university text-primary"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 ms-2">
                                        <p class="mb-0 small fw-semibold">৳<?php echo e(number_format($withdrawal->amount, 0)); ?></p>
                                        <small class="text-muted">
                                            <?php if($withdrawal->status == 'pending'): ?>
                                                <span class="text-warning">পেন্ডিং</span>
                                            <?php elseif($withdrawal->status == 'approved'): ?>
                                                <span class="text-success">অনুমোদিত</span>
                                            <?php else: ?>
                                                <span class="text-danger">রিজেক্ট</span>
                                            <?php endif; ?>
                                        </small>
                                    </div>
                                </div>
                            </a>
                        </li>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        <?php endif; ?>
                        
                        <!-- Empty State -->
                        <?php if((!isset($resellerPendingOrders) || $resellerPendingOrders == 0) && 
                            (!isset($resellerPendingWithdrawals) || $resellerPendingWithdrawals == 0) && 
                            (isset($resellerVerificationStatus) && $resellerVerificationStatus == 'approved')): ?>
                        <li>
                            <div class="px-3 py-4 text-center text-muted">
                                <i class="fas fa-check-circle fa-2x mb-2 opacity-25"></i>
                                <p class="mb-0 small">কোন নোটিফিকেশন নেই</p>
                            </div>
                        </li>
                        <?php endif; ?>
                        
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item text-center text-primary fw-semibold" href="<?php echo e(route('reseller.orders')); ?>">
                                সব দেখুন <i class="fas fa-arrow-right ms-1"></i>
                            </a>
                        </li>
                    </ul>
                </div>

                <div class="dropdown">
                    <a href="#" class="d-flex align-items-center text-decoration-none" data-bs-toggle="dropdown">
                        <?php if($user->image): ?>
                            <img src="<?php echo e(asset($user->image)); ?>" class="rounded-circle border" width="40" height="40" alt="Profile">
                        <?php else: ?>
                            <img src="https://i.pravatar.cc/150?img=11" class="rounded-circle border" width="40" height="40" alt="Profile">
                        <?php endif; ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 mt-3 p-2">
                        <li><a class="dropdown-item rounded" href="<?php echo e(route('reseller.settings')); ?>">প্রোফাইল</a></li>
                        <li><a class="dropdown-item rounded" href="<?php echo e(route('reseller.wallet')); ?>">ব্যালেন্স দেখুন</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form action="<?php echo e(route('reseller.logout')); ?>" method="POST" class="d-inline w-100 demo-allow-logout">
                                <?php echo csrf_field(); ?>
                                <button type="submit" class="dropdown-item rounded text-danger w-100 text-start border-0 bg-transparent">লগ আউট</button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
        </header>

        <div class="p-4">
        <?php if(session('success')): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo e(session('success')); ?>

                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        <?php if(session('error') && !session('demo_mode_blocked')): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo e(session('error')); ?>

                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php echo $__env->yieldContent('content'); ?>
    </div>
    </main>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <?php if(Session::has('demo_mode_blocked')): ?>
    <script>
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            icon: 'info',
            title: '<strong style="font-size:1.4rem;color:#2c3e50;">ডেমো মুড সক্রিয়</strong>',
            html: '<div style="text-align:center;padding:10px 0;"><div style="width:70px;height:70px;margin:0 auto 15px;background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);border-radius:50%;display:flex;align-items:center;justify-content:center;"><i class="fas fa-eye" style="font-size:32px;color:#fff;"></i></div><p style="font-size:1rem;color:#5a6c7d;margin-bottom:8px;line-height:1.6;">ডেমো মুড চালু আছে। কোন ডাটা পরিবর্তন বা সংযোজন করা যাবে না।</p><p style="font-size:0.9rem;color:#95a5a6;margin:0;">কাস্টমার সাইটে অর্ডার, ট্রাকিং ও অন্যান্য সেবা স্বাভাবিকভাবে কাজ করবে।</p></div>',
            confirmButtonText: 'বুঝেছি',
            confirmButtonColor: '#667eea',
            customClass: { popup: 'demo-mode-popup', confirmButton: 'demo-mode-btn' },
            width: '420px',
            backdrop: 'rgba(0,0,0,0.5)'
        });
    }
    </script>
    <?php endif; ?>
    <style>.demo-mode-popup{border-radius:12px;box-shadow:0 10px 40px rgba(0,0,0,0.2);}.demo-mode-btn{padding:10px 28px;font-weight:600;border-radius:8px;}</style>
    <script>
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');
        const toggleBtn = document.getElementById('sidebarToggle');

    // Toggle Sidebar
    if (toggleBtn) {
        toggleBtn.addEventListener('click', () => {
            sidebar.classList.add('show');
            overlay.classList.add('show');
        });
    }

    // Close Sidebar when clicking overlay
    if (overlay) {
        overlay.addEventListener('click', () => {
            sidebar.classList.remove('show');
            overlay.classList.remove('show');
        });
    }
    </script>
    <?php if(isset($demoMode) && $demoMode): ?>
    <script>
    function showDemoModeAlert(msg) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'info',
                title: '<strong style="font-size:1.4rem;color:#2c3e50;">ডেমো মুড সক্রিয়</strong>',
                html: '<div style="text-align:center;padding:10px 0;"><div style="width:70px;height:70px;margin:0 auto 15px;background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);border-radius:50%;display:flex;align-items:center;justify-content:center;"><i class="fas fa-eye" style="font-size:32px;color:#fff;"></i></div><p style="font-size:1rem;color:#5a6c7d;margin-bottom:8px;line-height:1.6;">' + (msg || 'ডেমো মুড চালু আছে। কোন ডাটা পরিবর্তন বা সংযোজন করা যাবে না।') + '</p><p style="font-size:0.9rem;color:#95a5a6;margin:0;">কাস্টমার সাইটে অর্ডার, ট্রাকিং ও অন্যান্য সেবা স্বাভাবিকভাবে কাজ করবে।</p></div>',
                confirmButtonText: 'বুঝেছি',
                confirmButtonColor: '#667eea',
                customClass: { popup: 'demo-mode-popup', confirmButton: 'demo-mode-btn' },
                width: '420px',
                backdrop: 'rgba(0,0,0,0.5)'
            });
        }
    }
    $(document).ajaxComplete(function(event, xhr) {
        if (xhr.status === 403) {
            try {
                var data = typeof xhr.responseJSON !== 'undefined' ? xhr.responseJSON : JSON.parse(xhr.responseText || '{}');
                if (data.demo_mode && typeof Swal !== 'undefined') showDemoModeAlert(data.message || '');
            } catch (e) {}
        }
    });
    $(document).on('submit', 'form', function(e) {
        if ($(this).hasClass('demo-allow-logout')) return;
        var action = (this.action || '').toLowerCase();
        if (action.indexOf('logout') !== -1) return;
        var method = ($(this).find('input[name="_method"]').val() || $(this).attr('method') || 'get').toLowerCase();
        if (method === 'get') return;
        e.preventDefault();
        showDemoModeAlert();
        return false;
    });
    $(document).on('click', '.delete-confirm, .change-confirm', function(e) {
        e.preventDefault();
        showDemoModeAlert();
        return false;
    });
    document.addEventListener('click', function(e) {
        var el = e.target.closest ? e.target.closest('a[href*="destroy"], a[href*="delete"]') : null;
        if (el && el.href && el.href.indexOf('#') !== 0) {
            e.preventDefault();
            e.stopPropagation();
            showDemoModeAlert();
            return false;
        }
    }, true);
    </script>
    <?php endif; ?>
    <?php echo $__env->yieldPushContent('scripts'); ?>
</body>
</html>
<?php /**PATH C:\laragon\www\bilaiGhor\resources\views\reseller\layouts\app.blade.php ENDPATH**/ ?>