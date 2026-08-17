<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	<meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">

	<title>Forgot Password | <?php echo e($generalsetting->name); ?></title>
	<link rel="shortcut icon" href="<?php echo e(asset($generalsetting->favicon)); ?>" alt="<?php echo e($generalsetting->name); ?>" />

	<link rel="stylesheet" href="<?php echo e(asset('public/backEnd/')); ?>/assets_login/css/vendors.css">
	<link rel="stylesheet" href="<?php echo e(asset('public/backEnd/')); ?>/assets_login/css/aiz-core.css">
</head>
<body>

<div class="aiz-main-wrapper d-flex">
	<div class="flex-grow-1">
		<div class="h-100 bg-cover bg-center py-5 d-flex align-items-center" style="background-image: url(<?php echo e(asset('public/backEnd/')); ?>/assets_login/img/background.jpg)">
			<div class="container">
				<div class="row">
					<div class="col-lg-6 col-xl-4 mx-auto">
						<div class="card text-left">
							<div class="card-body">
								<div class="mb-5 text-center">
									<img src="<?php echo e(asset($generalsetting->dark_logo)); ?>" class="mw-100 mb-4" height="40">
									<h1 class="h3 text-primary mb-0">Forgot Password</h1>
									<p>Enter your email to reset password</p>
								</div>

								<?php if(session('status')): ?>
									<div class="alert alert-success">
										<?php echo e(session('status')); ?>

									</div>
								<?php endif; ?>

								<?php if($errors->any()): ?>
									<div class="alert alert-danger">
										<?php echo e($errors->first()); ?>

									</div>
								<?php endif; ?>

								<form method="POST" action="<?php echo e(route('admin.password.email')); ?>">
									<?php echo csrf_field(); ?>
									<div class="form-group">
										<input id="email" type="email" name="email" class="form-control <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" placeholder="Enter Email" required autofocus>
										<?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
											<span class="invalid-feedback" role="alert">
												<strong><?php echo e($message); ?></strong>
											</span>
										<?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
									</div>

									<button type="submit" class="btn btn-primary btn-lg btn-block">
										Send Password Reset Link
									</button>
								</form>

								<div class="mt-3 text-center">
									<a href="<?php echo e(route('login')); ?>" class="text-reset fs-14">Back to Login</a>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<script src="<?php echo e(asset('public/backEnd/')); ?>/assets_login/js/vendors.js"></script>
<script src="<?php echo e(asset('public/backEnd/')); ?>/assets_login/js/aiz-core.js"></script>
</body>
</html>
<?php /**PATH C:\laragon\www\bilaiGhor\resources\views\backEnd\auth\passwords\email.blade.php ENDPATH**/ ?>