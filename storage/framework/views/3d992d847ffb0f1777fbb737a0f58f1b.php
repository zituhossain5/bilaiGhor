
<?php $__env->startSection('title', 'Facebook Page - Auto Post Products'); ?>

<?php $__env->startSection('content'); ?>
<div class="container-fluid py-3">
  <div class="row">
    <div class="col-12">
      <h4 class="fw-bold mb-3"><i class="fe-facebook text-primary me-2"></i> Facebook Page - Auto Post Products</h4>
      <p class="text-muted">Configure your Facebook Page to auto-post or manually post products. Use {name}, {price}, {link}, {description} in post template.</p>
    </div>
  </div>

  <form action="<?php echo e(route('admin.facebook_page.save_settings')); ?>" method="POST">
    <?php echo csrf_field(); ?>

    <div class="card">
      <div class="card-body">
        <div class="row g-3">
          <div class="col-md-6">
            <label>Page ID</label>
            <input type="text" name="page_id" class="form-control" value="<?php echo e($setting->page_id ?? ''); ?>" placeholder="e.g. 123456789012345" required>
            <small class="text-muted">Find in Page Settings → About</small>
          </div>
          <div class="col-md-6">
            <label>Page Access Token</label>
            <input type="password" name="page_access_token" class="form-control" value="<?php echo e($setting->page_access_token ?? ''); ?>" placeholder="Long-lived Page token">
            <small class="text-muted">Generate from <a href="https://developers.facebook.com/tools/explorer/" target="_blank">Graph API Explorer</a> with pages_manage_posts, pages_read_engagement</small>
          </div>
          <div class="col-md-6">
            <label>Page Name (optional)</label>
            <input type="text" name="page_name" class="form-control" value="<?php echo e($setting->page_name ?? ''); ?>" placeholder="Your Page Name">
          </div>
          <div class="col-md-6">
            <div class="form-check form-switch mt-4">
              <input class="form-check-input" type="checkbox" name="auto_post_new_products" value="1" <?php echo e(($setting->auto_post_new_products ?? false) ? 'checked' : ''); ?>>
              <label class="form-check-label">Auto-post when new product is created</label>
            </div>
          </div>
          <div class="col-12">
            <label>Post Template</label>
            <textarea name="post_template" class="form-control" rows="4" placeholder="Default: New Product! {name} - ৳{price}. Order: {link}"><?php echo e($setting->post_template ?? "🛒 New Product!\n\n{name}\n\nPrice: ৳{price}\n\nOrder now: {link}"); ?></textarea>
            <small class="text-muted">Use: {name}, {price}, {link}, {description}</small>
          </div>
        </div>
        <button type="submit" class="btn btn-primary mt-3"><i class="fe-save"></i> Save</button>
      </div>
    </div>
  </form>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('backEnd.layouts.master', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\bilaiGhor\resources\views/backEnd/facebook_page/settings.blade.php ENDPATH**/ ?>