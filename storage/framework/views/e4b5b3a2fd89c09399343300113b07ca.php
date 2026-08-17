
<?php if(!empty($landing->gtm_id)): ?>
<?php $gtm_noscript_id = preg_match('/^GTM-/i', trim($landing->gtm_id)) ? trim($landing->gtm_id) : 'GTM-'.trim($landing->gtm_id); ?>
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=<?php echo e($gtm_noscript_id); ?>"
height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
<?php endif; ?>
<?php /**PATH C:\laragon\www\bilaiGhor\resources\views\reseller\landing\partials\tracking-body.blade.php ENDPATH**/ ?>