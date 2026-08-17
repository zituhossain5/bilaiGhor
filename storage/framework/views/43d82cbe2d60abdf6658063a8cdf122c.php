
<?php $__env->startSection('title', 'TikTok Ads Result'); ?>

<?php $__env->startSection('css'); ?>
<style>
.ads-card { background:#fff; border-radius:12px; padding:25px; box-shadow:0 2px 12px rgba(0,0,0,.06); border-left:6px solid #000; }
.metric-row { display:flex; flex-wrap:wrap; gap:20px; margin-top:20px; }
.metric-item { flex:1; min-width:120px; padding:20px; background:#f5f5f5; border-radius:10px; text-align:center; }
.metric-value { font-size:24px; font-weight:700; color:#000; }
.metric-label { font-size:12px; color:#64748b; text-transform:uppercase; margin-top:5px; }
</style>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<div class="container-fluid py-3">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h4 class="fw-bold mb-1"><i class="fe-video me-2"></i> TikTok Ads Result</h4>
      <small class="text-muted">Live performance data from TikTok Ads</small>
    </div>
    <div>
      <span class="badge bg-success me-2"><i class="fe-radio"></i> Live</span>
      <a href="<?php echo e(route('admin.ads_analytics.tiktok', ['refresh' => 1])); ?>" class="btn btn-sm btn-dark">
        <i class="fe-refresh-cw"></i> Refresh
      </a>
      <a href="<?php echo e(route('admin.ads_analytics.dashboard')); ?>" class="btn btn-sm btn-outline-secondary">Overview</a>
      <a href="<?php echo e(route('admin.ads_analytics.settings')); ?>" class="btn btn-sm btn-outline-secondary">Settings</a>
    </div>
  </div>

  <div class="ads-card">
    <?php if(($tiktok['success'] ?? false)): ?>
      <h5 class="fw-bold mb-4">Today's Performance</h5>
      <div class="metric-row">
        <div class="metric-item">
          <div class="metric-value">$<?php echo e(number_format($tiktok['spend'] ?? 0, 2)); ?></div>
          <div class="metric-label">Spend</div>
        </div>
        <div class="metric-item">
          <div class="metric-value"><?php echo e(number_format($tiktok['clicks'] ?? 0)); ?></div>
          <div class="metric-label">Clicks</div>
        </div>
        <div class="metric-item">
          <div class="metric-value"><?php echo e(number_format($tiktok['impressions'] ?? 0)); ?></div>
          <div class="metric-label">Impressions</div>
        </div>
        <div class="metric-item">
          <div class="metric-value"><?php echo e(number_format($tiktok['reach'] ?? 0)); ?></div>
          <div class="metric-label">Reach</div>
        </div>
        <div class="metric-item">
          <div class="metric-value"><?php echo e(number_format($tiktok['conversions'] ?? 0)); ?></div>
          <div class="metric-label">Conversions</div>
        </div>
      </div>
    <?php else: ?>
      <p class="text-muted mb-3"><?php echo e($tiktok['message'] ?? 'Configure TikTok Ads API in Settings'); ?></p>
      <a href="<?php echo e(route('admin.ads_analytics.settings')); ?>" class="btn btn-dark">Configure API</a>
    <?php endif; ?>
  </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('backEnd.layouts.master', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\bilaiGhor\resources\views\backEnd\ads_analytics\tiktok.blade.php ENDPATH**/ ?>