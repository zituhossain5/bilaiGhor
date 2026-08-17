
<?php $__env->startSection('title','Request Refund'); ?>

<?php $__env->startSection('content'); ?>
<section class="customer-section">
    <div class="container">
        <div class="row">

            <div class="col-sm-3">
                <div class="customer-sidebar">
                    <?php echo $__env->make('frontEnd.layouts.customer.sidebar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                </div>
            </div>

            <div class="col-sm-9">
                <div class="customer-content">
                   <h5 class="account-title" style="color:#000;">Request Refund</h5>

                   <div class="card">
                       <div class="card-header bg-primary text-white">
                           <h6 class="mb-0">Order Information</h6>
                       </div>
                       <div class="card-body">
                           <div class="row">
                               <div class="col-md-6">
                                   <p><strong>Order Invoice:</strong> #<?php echo e($order->invoice_id); ?></p>
                                   <p><strong>Order Date:</strong> <?php echo e($order->created_at->format('d-m-Y h:i A')); ?></p>
                                   <p><strong>Order Status:</strong> 
                                       <span class="badge bg-secondary"><?php echo e($order->status ? $order->status->name : 'Pending'); ?></span>
                                   </p>
                               </div>
                               <div class="col-md-6">
                                   <p><strong>Total Amount:</strong> ৳<?php echo e(number_format($order->amount, 2)); ?></p>
                                   <p><strong>Shipping Charge:</strong> ৳<?php echo e(number_format($order->shipping_charge, 2)); ?></p>
                                   <p><strong>Grand Total:</strong> 
                                       <strong class="text-primary">৳<?php echo e(number_format($order->amount + $order->shipping_charge, 2)); ?></strong>
                                   </p>
                               </div>
                           </div>

                           <div class="mt-3">
                               <h6>Order Items:</h6>
                               <table class="table table-sm table-bordered">
                                   <thead>
                                       <tr>
                                           <th>Product</th>
                                           <th>Qty</th>
                                           <th>Price</th>
                                       </tr>
                                   </thead>
                                   <tbody>
                                       <?php $__currentLoopData = $order->orderdetails; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                           <tr>
                                               <td><?php echo e($item->product_name); ?></td>
                                               <td><?php echo e($item->qty); ?></td>
                                               <td>৳<?php echo e(number_format($item->sale_price * $item->qty, 2)); ?></td>
                                           </tr>
                                       <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                   </tbody>
                               </table>
                           </div>
                       </div>
                   </div>

                   <form action="<?php echo e(route('customer.refunds.store')); ?>" method="POST" class="mt-4">
                       <?php echo csrf_field(); ?>
                       <input type="hidden" name="order_id" value="<?php echo e($order->id); ?>">

                       <div class="card">
                           <div class="card-header bg-warning text-dark">
                               <h6 class="mb-0">Refund Details</h6>
                           </div>
                           <div class="card-body">
                               <div class="row">
                                   <div class="col-md-6 mb-3">
                                       <label for="amount" class="form-label">Refund Amount <span class="text-danger">*</span></label>
                                       <input type="number" 
                                              class="form-control <?php $__errorArgs = ['amount'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" 
                                              id="amount" 
                                              name="amount" 
                                              value="<?php echo e(old('amount', $order->amount)); ?>" 
                                              min="1" 
                                              max="<?php echo e($order->amount); ?>" 
                                              step="0.01" 
                                              required>
                                       <small class="text-muted">Maximum: ৳<?php echo e(number_format($order->amount, 2)); ?></small>
                                       <?php $__errorArgs = ['amount'];
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

                                   <div class="col-md-6 mb-3">
                                       <label for="shipping_charge" class="form-label">Shipping Charge Refund</label>
                                       <input type="number" 
                                              class="form-control <?php $__errorArgs = ['shipping_charge'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" 
                                              id="shipping_charge" 
                                              name="shipping_charge" 
                                              value="<?php echo e(old('shipping_charge', $order->shipping_charge)); ?>" 
                                              min="0" 
                                              max="<?php echo e($order->shipping_charge); ?>" 
                                              step="0.01">
                                       <small class="text-muted">Maximum: ৳<?php echo e(number_format($order->shipping_charge, 2)); ?></small>
                                       <?php $__errorArgs = ['shipping_charge'];
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

                               <div class="mb-3">
                                   <label for="reason" class="form-label">Reason for Refund <span class="text-danger">*</span></label>
                                   <textarea class="form-control <?php $__errorArgs = ['reason'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" 
                                             id="reason" 
                                             name="reason" 
                                             rows="4" 
                                             required 
                                             placeholder="Please explain why you want a refund..."><?php echo e(old('reason')); ?></textarea>
                                   <?php $__errorArgs = ['reason'];
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
                                   <label for="refund_method" class="form-label">Refund Method <span class="text-danger">*</span></label>
                                   <select class="form-control <?php $__errorArgs = ['refund_method'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" 
                                           id="refund_method" 
                                           name="refund_method" 
                                           required>
                                       <option value="">Select Method</option>
                                       <option value="original_payment" <?php echo e(old('refund_method') == 'original_payment' ? 'selected' : ''); ?>>Original Payment Method</option>
                                       <option value="bkash" <?php echo e(old('refund_method') == 'bkash' ? 'selected' : ''); ?>>bKash</option>
                                       <option value="nagad" <?php echo e(old('refund_method') == 'nagad' ? 'selected' : ''); ?>>Nagad</option>
                                       <option value="bank" <?php echo e(old('refund_method') == 'bank' ? 'selected' : ''); ?>>Bank Transfer</option>
                                       <option value="manual" <?php echo e(old('refund_method') == 'manual' ? 'selected' : ''); ?>>Manual/Cash</option>
                                   </select>
                                   <?php $__errorArgs = ['refund_method'];
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
                                   <label for="refund_account" class="form-label">Account Number/Phone <span class="text-danger">*</span></label>
                                   <input type="text" 
                                          class="form-control <?php $__errorArgs = ['refund_account'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" 
                                          id="refund_account" 
                                          name="refund_account" 
                                          value="<?php echo e(old('refund_account')); ?>" 
                                          required 
                                          placeholder="Enter bKash/Nagad number or Bank account number">
                                   <?php $__errorArgs = ['refund_account'];
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
                                   <label for="refund_account_name" class="form-label">Account Holder Name</label>
                                   <input type="text" 
                                          class="form-control <?php $__errorArgs = ['refund_account_name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" 
                                          id="refund_account_name" 
                                          name="refund_account_name" 
                                          value="<?php echo e(old('refund_account_name')); ?>" 
                                          placeholder="Enter account holder name (if applicable)">
                                   <?php $__errorArgs = ['refund_account_name'];
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

                               <div class="alert alert-info">
                                   <i class="fa fa-info-circle"></i> 
                                   <strong>Note:</strong> Your refund request will be reviewed by our admin team. You will be notified once the refund is processed.
                               </div>

                               <div class="mt-4">
                                   <button type="submit" class="btn btn-primary">
                                       <i class="fa fa-paper-plane"></i> Submit Refund Request
                                   </button>
                                   <a href="<?php echo e(route('customer.orders')); ?>" class="btn btn-secondary">
                                       <i class="fa fa-arrow-left"></i> Back to Orders
                                   </a>
                               </div>
                           </div>
                       </div>
                   </form>
                </div>
            </div>
        </div>
    </div>
</section>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('frontEnd.layouts.master', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\bilaiGhor\resources\views\frontEnd\layouts\customer\refund_request.blade.php ENDPATH**/ ?>