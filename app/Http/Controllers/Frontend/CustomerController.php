<?php

namespace App\Http\Controllers\Frontend;

// use shurjopayv2\ShurjopayLaravelPackage8\Http\Controllers\ShurjopayController;
use App\Mail\OrderPlace;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Brian2694\Toastr\Facades\Toastr;
use Intervention\Image\Facades\Image;
use App\Models\Customer;
use App\Models\District;
use App\Models\Order;
use App\Models\OrderDetails;
use App\Models\Payment;
use App\Models\Shipping;
use App\Models\Review;
use App\Models\PaymentGateway;
use App\Models\ManualPaymentGateway;
use App\Models\DeliveryDivision;
use App\Support\DeliveryLocation;
use App\Models\SmsGateway;
use App\Helpers\SmsHelper;
use App\Models\Contact;
use App\Models\GeneralSetting;
use App\Models\IncompleteOrder;
use App\Models\Product;          // স্টক কমানোর জন্য
use App\Models\DigitalDownload;  // ⭐ ডিজিটাল ডাউনলোড মডেল

use Session;
use Hash;
use Auth;
use Cart;
use Mail;
use Str;
use DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash as HashFacade;
use Illuminate\Support\Facades\File;
use App\Helpers\OrderHelper;
use App\Services\BdCourierService;
use App\Services\FacebookCapiService;
use App\Http\Controllers\Concerns\HandlesCheckoutOtp;

class CustomerController extends Controller
{
    use HandlesCheckoutOtp;

    protected $facebookCapiService;

    function __construct(FacebookCapiService $facebookCapiService)
    {
        $this->facebookCapiService = $facebookCapiService;
        // 'delivery_zones' is guest-accessible: checkout (open to guests) loads zones
        // from it. It only returns active zones of a district — no customer data.
        // 'wishlist_toggle' handles guests itself (401 JSON → login prompt in JS,
        // not a middleware redirect).
        $this->middleware('customer', ['except' => [
            'register','store','verify','resendotp','account_verify',
            'login','signin','logout','checkout','forgot_password',
            'forgot_verify','forgot_reset','forgot_store','forgot_resend',
            'order_save','order_success','order_track','order_track_result',
            'delivery_zones','wishlist_toggle'
        ]]);
    }

    public function review(Request $request)
    {
        $this->validate($request, [
            'ratting' => 'required|integer|min:1|max:5',
            'review'  => 'required',
            'image'   => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        $review = new Review();
        $review->name       = Auth::guard('customer')->user()->name ?? 'N / A';
        $review->email      = Auth::guard('customer')->user()->email ?? 'N / A';
        $review->product_id = $request->product_id;
        $review->review     = $request->review;
        $review->ratting    = $request->ratting;
        $review->customer_id = Auth::guard('customer')->user()->id;
        $review->status     = 'pending';

        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $name = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('review_images'), $name);
            $review->image = 'public/review_images/' . $name;
        }

        $review->save();

        Toastr::success('Thanks, Your review send successfully', 'Success!');
        return redirect()->back();
    }

    public function login(Request $request)
    {
        $previous = url()->previous();
        $current = $request->fullUrl();

        if ($previous
            && $previous !== $current
            && Str::startsWith($previous, url('/'))
            && !Str::contains($previous, ['/customer/login', '/customer/register', '/customer/auth/'])
        ) {
            $request->session()->put('url.intended', $previous);
        }

        return view('frontEnd.layouts.customer.login');
    }

    public function signin(Request $request)
    {
        $request->validate([
            'login'    => 'required|string',
            'password' => 'required|string',
        ]);

        $login    = $request->input('login');   // phone or email
        $password = $request->input('password');
        $remember = $request->boolean('remember');

        // Check if login is phone number and if it belongs to a vendor or reseller
        $isVendorPhone = false;
        $isResellerPhone = false;
        $vendor = null;
        $resellerUser = null;

        if (preg_match('/^[0-9+]+$/', $login)) {
            // Check if it's a vendor phone
            $vendor = \App\Models\Vendor::where('phone', $login)->first();
            if ($vendor) {
                $isVendorPhone = true;
            }

            // Check if it's a reseller phone (via customer record email matching)
            $customer = Customer::where('phone', $login)->first();
            if ($customer && $customer->email) {
                $resellerUser = \App\Models\User::where('email', $customer->email)
                    ->where(function($query) {
                        $query->where('role', 'reseller')
                              ->orWhereHas('roles', function($q) {
                                  $q->where('name', 'reseller');
                              });
                    })
                    ->first();
                if ($resellerUser) {
                    $isResellerPhone = true;
                }
            }
        }

        // 1) Try customer (phone-based) - only if not a vendor or reseller phone
        // Also check if customer exists with this phone number
        if (!$isVendorPhone && !$isResellerPhone && preg_match('/^[0-9+]+$/', $login)) {
            $customerExists = Customer::where('phone', $login)->exists();
            if ($customerExists && Auth::guard('customer')->attempt(['phone' => $login, 'password' => $password], $remember)) {
                Toastr::success('You are login successfully', 'success!');
                if (Cart::instance('shopping')->count() > 0) {
                    return redirect()->route('customer.checkout');
                }
                return redirect()->intended('customer/account');
            }
        }

        // If reseller phone, use admin guard with reseller email
        if ($isResellerPhone && $resellerUser) {
            $adminCredentials = ['email' => $resellerUser->email, 'password' => $password];
        }

        // 2) Try vendor/admin/reseller via admin guard (email mapped from vendor/reseller phone or direct email)
        if (!isset($adminCredentials)) {
            $adminCredentials = null;
        }

        // If login looks like a phone number, map to vendor email (if not already set for reseller)
        if (!isset($adminCredentials) && preg_match('/^[0-9+]+$/', $login)) {
            // Re-check vendor if not already checked
            if (!$vendor) {
                $vendor = \App\Models\Vendor::where('phone', $login)->first();
            }
            if ($vendor) {
                $adminCredentials = ['email' => $vendor->email, 'password' => $password];
            }
        } elseif (filter_var($login, FILTER_VALIDATE_EMAIL)) {
            // Email input - check if it's a reseller email first
            $resellerUser = \App\Models\User::where('email', $login)
                ->where(function($query) {
                    $query->where('role', 'reseller')
                          ->orWhereHas('roles', function($q) {
                              $q->where('name', 'reseller');
                          });
                })
                ->first();

            if ($resellerUser) {
                // It's a reseller email, use admin guard
                $adminCredentials = ['email' => $login, 'password' => $password];
            } else {
                // Check if it's a vendor email
                $vendor = \App\Models\Vendor::where('email', $login)->first();
                if ($vendor) {
                    // It's a vendor email, use admin guard
                    $adminCredentials = ['email' => $login, 'password' => $password];
                } else {
                    // Check if it's a customer email (but not a reseller)
                    $customerExists = Customer::where('email', $login)->exists();
                    // Also check if this customer email is not linked to a reseller
                    $isResellerCustomer = \App\Models\User::where('email', $login)
                        ->where(function($query) {
                            $query->where('role', 'reseller')
                                  ->orWhereHas('roles', function($q) {
                                      $q->where('name', 'reseller');
                                  });
                        })
                        ->exists();

                    if ($customerExists && !$isResellerCustomer && Auth::guard('customer')->attempt(['email' => $login, 'password' => $password], $remember)) {
                        Toastr::success('You are login successfully', 'success!');
                        if (Cart::instance('shopping')->count() > 0) {
                            return redirect()->route('customer.checkout');
                        }
                        return redirect()->intended('customer/account');
                    }
                    // Try admin/user email
                    $adminCredentials = ['email' => $login, 'password' => $password];
                }
            }
        }

        if ($adminCredentials && Auth::guard('admin')->attempt($adminCredentials)) {
            $user = Auth::guard('admin')->user();

            // Check if user has reseller role, redirect to reseller dashboard
            // Check both Spatie role and direct role column
            $isReseller = $user->hasRole('reseller') ||
                          (isset($user->role) && strtolower($user->role) === 'reseller') ||
                          $user->getRoleNames()->contains('reseller');

            if ($isReseller) {
                Toastr::success('You are login successfully', 'success!');
                return redirect()->route('reseller.dashboard');
            }

            if ($user->hasRole('vendor')) {
                Toastr::success('You are login successfully', 'success!');
                return redirect()->route('vendor.dashboard');
            }
            if ($user->hasRole('admin')) {
                Toastr::success('You are login successfully', 'success!');
                return redirect()->route('admin.dashboard');
            }

            // Unknown role -> logout and show error
            Auth::guard('admin')->logout();
            Toastr::error('Role not allowed for this login path', 'Error');
            return redirect()->back();
        }

        // Failed
        Toastr::error('Opps! your credentials are wrong', 'Error');
        return redirect()->back()->withInput($request->only('login'));
    }

    public function register()
    {
        return view('frontEnd.layouts.customer.register');
    }

    public function store(Request $request)
    {
        $isReseller = $request->has('is_reseller') && $request->is_reseller == '1';
        $isSeller = $request->has('is_seller') && $request->is_seller == '1';

        if ($isReseller) {
            // Reseller registration — ডকিমেন্ট ড্যাশবোর্ডের ভেরিফিকেশন পেজে (Reseller\\VerificationController)
            $request->validate([
                'name'               => 'required|string|max:255',
                'phone'              => 'required|string|max:55|unique:customers,phone',
                'email'              => 'required|email|unique:users,email|unique:customers,email',
                'reseller_shop_name' => 'required|string|max:255',
                'password'           => 'required|confirmed|min:6',
                'voter_id_front'     => 'nullable|image|mimes:jpeg,jpg,png,webp|max:102400',
                'voter_id_back'      => 'nullable|image|mimes:jpeg,jpg,png,webp|max:102400',
                'self_image'         => 'nullable|image|mimes:jpeg,jpg,png,webp|max:102400',
            ]);

            // Upload verification documents
            $voterFrontPath = null;
            $voterBackPath = null;
            $selfImagePath = null;

            if ($request->hasFile('voter_id_front')) {
                $frontImage = $request->file('voter_id_front');
                $frontName = time() . '-voter-front-' . uniqid() . '.webp';
                $frontPath = 'public/uploads/reseller/verification/';

                if (!File::exists($frontPath)) {
                    File::makeDirectory($frontPath, 0755, true);
                }

                $img = Image::make($frontImage->getRealPath());
                $img->encode('webp', 90);
                $img->resize(800, 800, function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                });
                $img->save($frontPath . $frontName);
                $voterFrontPath = $frontPath . $frontName;
            }

            if ($request->hasFile('voter_id_back')) {
                $backImage = $request->file('voter_id_back');
                $backName = time() . '-voter-back-' . uniqid() . '.webp';
                $backPath = 'public/uploads/reseller/verification/';

                if (!File::exists($backPath)) {
                    File::makeDirectory($backPath, 0755, true);
                }

                $img = Image::make($backImage->getRealPath());
                $img->encode('webp', 90);
                $img->resize(800, 800, function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                });
                $img->save($backPath . $backName);
                $voterBackPath = $backPath . $backName;
            }

            if ($request->hasFile('self_image')) {
                $selfImage = $request->file('self_image');
                $selfName = time() . '-self-' . uniqid() . '.webp';
                $selfPath = 'public/uploads/reseller/verification/';

                if (!File::exists($selfPath)) {
                    File::makeDirectory($selfPath, 0755, true);
                }

                $img = Image::make($selfImage->getRealPath());
                $img->encode('webp', 90);
                $img->resize(600, 600, function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                });
                $img->save($selfPath . $selfName);
                $selfImagePath = $selfPath . $selfName;
            }

            // Create user account with reseller role
            $user = \App\Models\User::create([
                'name'                  => $request->name,
                'email'                 => $request->email,
                'password'              => HashFacade::make($request->password),
                'status'                => 1,
                'role'                  => 'reseller',
                'shop_name'             => $request->reseller_shop_name,
                'verification_status'   => 'pending',
                'voter_id_front'        => $voterFrontPath,
                'voter_id_back'         => $voterBackPath,
                'self_image'            => $selfImagePath,
            ]);

            // Ensure reseller role exists and assign (using admin guard like vendors)
            $role = \Spatie\Permission\Models\Role::firstOrCreate(
                ['name' => 'reseller', 'guard_name' => 'admin'],
                ['name' => 'reseller', 'guard_name' => 'admin']
            );
            $user->assignRole($role);

            // Clear role cache to ensure role is immediately available
            app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

            // Also create customer record for phone-based login
            $last_id = Customer::orderBy('id', 'desc')->first();
            $last_id = $last_id?$last_id->id+1:1;

            $customer = new Customer();
            $customer->name = $request->name;
            $customer->slug = strtolower(Str::slug($request->name.'-'.$last_id));
            $customer->phone = $request->phone;
            $customer->email = $request->email;
            $customer->password = bcrypt($request->password);
            $customer->verify = 1;
            $customer->status = 'active';
            $customer->save();

            // Assign customer role to customer record
            $customerRole = \Spatie\Permission\Models\Role::firstOrCreate(
                ['name' => 'customer', 'guard_name' => 'customer'],
                ['name' => 'customer', 'guard_name' => 'customer']
            );
            $customer->assignRole($customerRole);

            // Auto login reseller after registration
            Auth::guard('admin')->login($user);

            Toastr::success('রিসেলার একাউন্ট তৈরি হয়েছে। ড্যাশবোর্ডের ভেরিফিকেশন পেজ থেকে এনআইডি ও ছবি আপলোড করে সম্পন্ন করুন।', 'Success');
            return redirect()->route('reseller.dashboard');
        } elseif ($isSeller) {
            // Vendor registration
            $request->validate([
                'name'                  => 'required|string|max:255',
                'phone'                 => 'required|string|max:55|unique:vendors,phone|unique:customers,phone',
                'email'                 => 'required|email|unique:users,email|unique:vendors,email|unique:customers,email',
                'shop_name'             => 'required|string|max:255',
                'slug'                  => 'required|string|max:255|unique:vendors,slug',
                'password'              => 'required|confirmed|min:6',
                'address'               => 'nullable|string',
                'logo'                  => 'nullable|image|max:2048',
                'banner'                => 'nullable|image|max:3072',
            ]);

            // Upload files if provided
            $logoPath = null;
            if ($request->hasFile('logo')) {
                $logoPath = $request->file('logo')->store('uploads/vendor/logo', 'public');
            }

            $bannerPath = null;
            if ($request->hasFile('banner')) {
                $bannerPath = $request->file('banner')->store('uploads/vendor/banner', 'public');
            }

            // Create vendor record first
            $vendor = \App\Models\Vendor::create([
                'shop_name'  => $request->shop_name,
                'slug'       => $request->slug,
                'owner_name' => $request->name,
                'email'      => $request->email,
                'phone'      => $request->phone,
                'address'    => $request->address ?? null,
                'logo'       => $logoPath,
                'banner'     => $bannerPath,
                'status'     => 1,
            ]);

            // Create user account with vendor_id
            $user = \App\Models\User::create([
                'name'      => $request->name,
                'email'     => $request->email,
                'password'  => HashFacade::make($request->password),
                'status'    => 1,
                'vendor_id' => $vendor->id,
            ]);

            // Ensure vendor role exists and assign
            $role = \Spatie\Permission\Models\Role::firstOrCreate(
                ['name' => 'vendor', 'guard_name' => 'admin'],
                ['name' => 'vendor', 'guard_name' => 'admin']
            );
            $user->assignRole($role);

            // Auto login vendor after registration
            Auth::guard('admin')->login($user);

            Toastr::success('ভেন্ডর একাউন্ট তৈরি হয়েছে। ড্যাশবোর্ডের ভেরিফিকেশন অংশ থেকে এনআইডি ও ছবি আপলোড করে ভেরিফিকেশন সম্পূর্ণ করুন।', 'Success');
            return redirect()->route('vendor.dashboard');
        } else {
            // Customer registration
            $this->validate($request, [
                'name'     => 'required',
                'phone'    => 'required|unique:customers',
                'password' => 'required|min:6'
            ]);

            $last_id = Customer::orderBy('id', 'desc')->first();
            $last_id = $last_id?$last_id->id+1:1;

            $store = new Customer();
            $store->name = $request->name;
            $store->slug = strtolower(Str::slug($request->name.'-'.$last_id));
            $store->phone = $request->phone;
            $store->email = $request->email ?? null;
            $store->password = bcrypt($request->password);
            $store->verify = 1;
            $store->status = 'active';
            $store->save();

            // Assign customer role
            $customerRole = \Spatie\Permission\Models\Role::firstOrCreate(
                ['name' => 'customer', 'guard_name' => 'customer'],
                ['name' => 'customer', 'guard_name' => 'customer']
            );
            $store->assignRole($customerRole);

            Toastr::success('Success','Account Create Successfully');
            return redirect()->route('customer.login');
        }
    }

    public function verify()
    {
        return view('frontEnd.layouts.customer.verify');
    }

    public function resendotp(Request $request)
    {
        $customer_info = Customer::where('phone',session::get('verify_phone'))->first();
        $customer_info->verify = rand(1111,9999);
        $customer_info->save();
        $site_setting = GeneralSetting::where('status', 1)->first();
        SmsHelper::send(
            $customer_info->phone,
            "Dear {$customer_info->name}!\r\nYour account verify OTP is {$customer_info->verify}\r\nThank you for using {$site_setting->name}"
        );

        Toastr::success('Success','Resend code send successfully');
        return redirect()->back();
    }

    public function account_verify(Request $request)
    {
        $this->validate($request,['otp' => 'required']);
        $customer_info = Customer::where('phone',session::get('verify_phone'))->first();

        if($customer_info->verify != $request->otp){
            Toastr::error('Success','Your OTP not match');
            return redirect()->back();
        }

        $customer_info->verify = 1;
        $customer_info->status = 'active';
        $customer_info->save();
        Auth::guard('customer')->loginUsingId($customer_info->id);
        return redirect()->route('customer.account');
    }

    // Legacy forgot-password methods (forgot_password / forgot_verify / forgot_resend /
    // forgot_reset / forgot_store) were removed: they stored a plain rand(1111,9999)
    // OTP in customers.forgot with no expiry, attempt limit, or rate limit.
    // The flow now lives in Frontend\CustomerPasswordResetController (email link + hashed SMS OTP).

    public function account()
    {
        return view('frontEnd.layouts.customer.account');
    }

    public function logout(Request $request)
    {
        Auth::guard('customer')->logout();

        // Rotate the session ID (fixation protection) and CSRF token, but keep
        // session data so the shopping cart survives logout.
        $request->session()->regenerate();
        $request->session()->regenerateToken();

        Toastr::success('You are logout successfully', 'success!');
        return redirect()->route('customer.login');
    }

    public function checkout()
    {
        $divisions = DeliveryDivision::active()->ordered()->get();
        $bkash_gateway = PaymentGateway::where(['status'=> 1, 'type'=>'bkash'])->first();
        // $shurjopay_gateway = PaymentGateway::where(['status'=> 1, 'type'=>'shurjopay'])->first();
        $uddoktapay_gateway = PaymentGateway::where(['status'=> 1, 'type'=>'uddoktapay'])->first();
        $aamarpay_gateway = PaymentGateway::where(['status'=> 1, 'type'=>'aamarpay'])->first();
        $manual_gateways = ManualPaymentGateway::enabled()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $hasAllFreeDelivery = \App\Http\Controllers\Frontend\ShoppingController::hasAllFreeDeliveryProducts();

        $requiresPhysicalShipping = false;
        foreach (Cart::instance('shopping')->content() as $item) {
            $product = Product::find($item->id);
            if ($product && (int) $product->is_digital !== 1) {
                $requiresPhysicalShipping = true;
                break;
            }
        }

        if (! $requiresPhysicalShipping || $hasAllFreeDelivery) {
            Session::put('shipping', 0);
            Session::put('shipping_district_id', null);
        }

        $hasDigital = \App\Http\Controllers\Frontend\ShoppingController::hasDigitalProductInCart();

        if (Auth::guard('admin')->check()) {
            $resellerUser = Auth::guard('admin')->user();
            $isReseller = $resellerUser->hasRole('reseller') ||
                          (isset($resellerUser->role) && strtolower($resellerUser->role) === 'reseller') ||
                          $resellerUser->getRoleNames()->contains('reseller');

            if ($isReseller && Cart::instance('shopping')->count() > 0) {
                return redirect()->route('reseller.checkout');
            }
        }

        \App\Http\Controllers\Frontend\ShoppingController::refreshCartWholesalePrices();

        // ── Districts for the checkout selects (same source as the Add/Edit Address popup) ──
        $checkoutDistricts = \App\Models\DeliveryDistrict::active()->ordered()->get(['id', 'name', 'delivery_charge']);

        // ── Checkout prefill for logged-in customers (own data only) ──
        // Priority: old() (handled in the view) → default saved address → profile → empty.
        // Previous orders are NOT used as a location source any more.
        $checkoutPrefill = [
            'name'        => '',
            'mobile'      => '',
            'address'     => '',
            'post_code'   => '',
            'district_id' => '',
            'zone_id'     => '',
        ];

        $authCustomer = Auth::guard('customer')->user();
        if ($authCustomer) {
            $checkoutPrefill['name']        = $authCustomer->name    ?: '';
            $checkoutPrefill['mobile']      = $authCustomer->phone   ?: '';
            $checkoutPrefill['address']     = $authCustomer->address ?: '';
            $checkoutPrefill['district_id'] = $authCustomer->district_id ?: '';
            $checkoutPrefill['zone_id']     = $authCustomer->zone_id ?: '';
        }

        // ── Saved addresses for the "Select Address" modal ──
        // Single source of truth: customer_addresses ONLY (same as the Addresses page).
        // No profile/previous-order fallbacks — a deleted address must vanish here too.
        $savedAddresses = [];
        if ($authCustomer) {
            $storedAddresses = \App\Models\CustomerAddress::where('customer_id', $authCustomer->id)
                ->orderByDesc('is_default')
                ->orderBy('id')
                ->get();

            foreach ($storedAddresses as $sa) {
                $savedAddresses[] = [
                    'id'          => $sa->id, // enables Edit in the checkout modal
                    'name'        => $sa->name ?? '',
                    'mobile'      => $sa->phone ?? '',
                    'email'       => $sa->email ?? '',
                    'post_code'   => $sa->post_code ?? '',
                    'zone_id'     => $sa->zone_id ?? '',
                    'address'     => $sa->address,
                    'division_id' => $sa->division_id ?? '',
                    'district_id' => $sa->district_id ?? '',
                    'upazila_id'  => $sa->upazila_id ?? '',
                ];
            }

            // Default saved address wins the form prefill (name/mobile/post code/address + district/zone).
            $defaultStored = $storedAddresses->firstWhere('is_default', true);
            if ($defaultStored) {
                $checkoutPrefill['name']      = $defaultStored->name ?: $checkoutPrefill['name'];
                $checkoutPrefill['mobile']    = $defaultStored->phone ?: $checkoutPrefill['mobile'];
                $checkoutPrefill['address']   = $defaultStored->address ?: $checkoutPrefill['address'];
                $checkoutPrefill['post_code'] = $defaultStored->post_code ?: $checkoutPrefill['post_code'];
                if ($defaultStored->district_id) {
                    $checkoutPrefill['district_id'] = $defaultStored->district_id;
                    $checkoutPrefill['zone_id']     = $defaultStored->zone_id ?? '';
                }
            }
        }

        return view('frontEnd.layouts.customer.checkout',compact(
            'divisions',
            'checkoutDistricts',
            'bkash_gateway',
            // 'shurjopay_gateway',
            'uddoktapay_gateway',
            'aamarpay_gateway',
            'manual_gateways',
            'hasDigital',
            'hasAllFreeDelivery',
            'checkoutPrefill',
            'savedAddresses'
        ));
    }

public function order_save(Request $request)
    {
        $this->validate($request,[
            'name'=>'required',
            'phone'=>'required',
            'address'=>'required',
        ]);

        if(Cart::instance('shopping')->count() <= 0) {
            Toastr::error('Your shopping empty', 'Failed!');
            return redirect()->back();
        }

        \App\Http\Controllers\Frontend\ShoppingController::refreshCartWholesalePrices();

        // ⭐ কার্টে ডিজিটাল প্রোডাক্ট আছে কি না চেক
        $hasDigital = \App\Http\Controllers\Frontend\ShoppingController::hasDigitalProductInCart();

        if ($hasDigital && $request->payment_method === 'cod') {
            Toastr::error('ডিজিটাল প্রোডাক্টের জন্য Cash On Delivery পাওয়া যায় না, অনুগ্রহ করে অনলাইন পেমেন্ট সিলেক্ট করুন।', 'Failed!');
            return redirect()->back();
        }

        $manualPayId = ManualPaymentGateway::manualIdFromPaymentMethod($request->payment_method);
        if ($manualPayId !== null) {
            $mg = ManualPaymentGateway::where('id', $manualPayId)->where('status', 1)->first();
            if (! $mg) {
                Toastr::error('ম্যানুয়াল পেমেন্ট অপশনটি আর উপলব্ধ নেই। আবার সিলেক্ট করুন।', 'Failed!');
                return redirect()->back();
            }
            $this->validate($request, [
                'manual_trx_id'        => ['required', 'string', 'max:55'],
                'manual_sender_number' => ['nullable', 'string', 'max:55'],
            ]);
        }

        $requiresPhysicalShipping = false;
        foreach (Cart::instance('shopping')->content() as $item) {
            $product = Product::find($item->id);
            if ($product && (int) $product->is_digital !== 1) {
                $requiresPhysicalShipping = true;
                break;
            }
        }
        $hasAllFreeDelivery = \App\Http\Controllers\Frontend\ShoppingController::hasAllFreeDeliveryProducts();

        // Checkout collects District → Zone (+ optional Post Code). Division is derived
        // from the district; upazila is no longer part of this flow (columns kept).
        $divisionId = null;
        $districtId = null;
        $upazilaId  = null;
        $zoneId     = null;
        $postCode   = null;

        if ($requiresPhysicalShipping && ! $hasAllFreeDelivery) {
            $this->validate($request, [
                'district_id' => 'required|integer|exists:districts,id',
                'zone_id'     => 'required|integer|exists:delivery_zones,id',
                'post_code'   => 'nullable|string|max:20',
            ]);
            $districtId = (int) $request->district_id;
            $zoneId     = (int) $request->zone_id;
            $postCode   = $request->post_code;

            if (! DeliveryLocation::validateDistrictZone($districtId, $zoneId)) {
                Toastr::error('জেলা ও জোন সঠিকভাবে নির্বাচন করুন।', 'Failed!');
                return redirect()->back()->withInput();
            }

            $divisionId = DeliveryLocation::divisionIdForDistrict($districtId);
        }

        $otpRedirect = $this->checkoutOtpGate($request, 'customer');
        if ($otpRedirect !== null) {
            return $otpRedirect;
        }

        // Amount ক্যালকুলেশন
        $subtotal = (float) str_replace([',','.00'],'',Cart::instance('shopping')->subtotal());
        $discount = Session::get('discount', 0);

        if ($requiresPhysicalShipping && ! $hasAllFreeDelivery) {
            $shippingfee = DeliveryLocation::chargeForDistrictId($districtId);
            Session::put('shipping', $shippingfee);
            Session::put('shipping_district_id', $districtId);
        } else {
            $shippingfee = 0;
            Session::put('shipping', 0);
            Session::put('shipping_district_id', null);
        }

        $locationLabelForGateway = ($districtId && $zoneId)
            ? DeliveryLocation::shippingLabelForZone($districtId, $zoneId)
            : 'BD';

        // ইনভয়েসে দেখানোর মোট (Grand Total)
        $grandTotal = ($subtotal + $shippingfee) - $discount;

        // Amount actually charged/recorded always equals the real grand total — Advance
        // Payment has been fully retired from this flow (was: partial "advance" amount
        // charged instead of the full total when a product had an advance_amount configured).
        $payable_amount = $grandTotal;

        // ── Reward points (logged-in customers only; amounts NEVER trusted from the form) ──
        // The form only sends use_reward_points=1/0. Points and discount are recomputed
        // here, then re-derived under a row lock inside the order transaction below.
        $useRewardPoints  = Auth::guard('customer')->check() && $request->boolean('use_reward_points');
        $rewardPointsUsed = 0;
        $rewardDiscount   = 0.0;
        $rewardPointValue = max(1, (int) config('rewards.point_value', 1));
        if ($useRewardPoints) {
            $eligibleSubtotal = max(0, $subtotal - $discount);
            $rewardPointsUsed = \App\Services\RewardPointService::maxRedeemable(Auth::guard('customer')->id(), $eligibleSubtotal);
            $rewardDiscount   = $rewardPointsUsed * $rewardPointValue;
            $grandTotal       = max(0, $grandTotal - $rewardDiscount);
            $payable_amount   = $grandTotal;
        }

        // Customer ঠিক করা
        if(Auth::guard('customer')->user()){
            $customer_id = Auth::guard('customer')->user()->id;
        }else{
            $exist = Customer::where('phone',$request->phone)->select('id')->first();
            if($exist){
                $customer_id = $exist->id;
            }else{
                $password = rand(111111,999999);
                $store = new Customer();
                $store->name = $request->name;
                $store->slug = Str::slug($request->name);
                $store->phone = $request->phone;
                $store->password = bcrypt($password);
                $store->verify = 1;
                $store->status = 'active';
                $store->save();
                $customer_id = $store->id;
            }
        }

        // Main Order save — stock gate, order creation, reward spending, order details
        // and the inventory reservation all live in ONE outer transaction (committed
        // right after the reservation below): a failure anywhere rolls back atomically.
        \Illuminate\Support\Facades\DB::beginTransaction();
        try {
            // Authoritative availability check. Locks the inventory rows (FOR UPDATE),
            // held until commit — two customers racing for the last unit serialize here.
            \App\Services\InventoryService::assertAvailable(
                collect(Cart::instance('shopping')->content())->map(fn ($c) => [
                    'product_id' => (int) $c->id,
                    'qty'        => (int) $c->qty,
                    'name'       => $c->name,
                ])->all()
            );
        } catch (\App\Exceptions\InsufficientStockException $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            Toastr::error($e->getMessage(), 'Stock Out!');
            return redirect()->back()->withInput();
        }

        $order = new Order();
        \Illuminate\Support\Facades\DB::transaction(function () use ($order, $request, $customer_id, $useRewardPoints, $rewardPointValue, $subtotal, $discount, $shippingfee, &$rewardPointsUsed, &$rewardDiscount, &$grandTotal, &$payable_amount) {
            if ($useRewardPoints) {
                // Re-derive under a customer row lock: two simultaneous checkouts
                // serialize here, so the same points can never be spent twice.
                \App\Services\RewardPointService::lockCustomer($customer_id);
                $eligibleSubtotal = max(0, $subtotal - $discount);
                $rewardPointsUsed = \App\Services\RewardPointService::maxRedeemable($customer_id, $eligibleSubtotal);
                $rewardDiscount   = $rewardPointsUsed * $rewardPointValue;
                $grandTotal       = max(0, ($subtotal + $shippingfee) - $discount - $rewardDiscount);
                $payable_amount   = $grandTotal;
            }

            $order->invoice_id      = rand(11111,99999);
            $order->amount          = $grandTotal; // অর্ডারে সবসময় টোটাল এমাউন্ট থাকবে
            $order->shipping_charge = $shippingfee;
            $order->customer_id     = $customer_id;
            $order->order_status    = 1;
            $order->note            = $request->note;
            $order->order_note      = $request->order_note;
            $order->payment_status  = 'pending';
            $order->coupon_code     = Session::get('coupon_code') ?? null;
            $order->discount        = $discount ?? 0;
            $order->reward_points_used      = $rewardPointsUsed;
            $order->reward_discount_amount  = $rewardDiscount;
            $order->ip_address      = $request->ip();
            // Traffic source — ফর্ম + সেশন (শেয়ার লিঙ্ক / referrer / fbclid)
            $rawSource = $request->input('traffic_source', session('order_traffic_source', 'direct'));
            $rawReferrer = $request->input('traffic_referrer', session('order_traffic_referrer', ''));
            $order->traffic_source = \App\Support\TrafficSourceDetector::normalize($rawSource);
            $order->traffic_referrer = \App\Support\TrafficSourceDetector::clip((string) $rawReferrer);

            $order->save();

            if ($rewardPointsUsed > 0) {
                \App\Services\RewardPointService::redeem($customer_id, $order, $rewardPointsUsed);
            }
        });

        // Shipping info
        $shipping = new Shipping();
        $shipping->order_id    = $order->id;
        $shipping->customer_id = $customer_id;
        $shipping->name        = $request->name;
        $shipping->phone       = $request->phone;
        $shipping->address     = $request->address;
        $shipping->post_code   = $postCode;
        $shipping->division_id = $divisionId;   // derived from the district (legacy column kept in sync)
        $shipping->district_id = $districtId;
        $shipping->zone_id     = $zoneId;
        $shipping->upazila_id  = $upazilaId;    // null now — column preserved for existing orders
        $shipping->area        = ($districtId && $zoneId)
            ? DeliveryLocation::shippingLabelForZone($districtId, $zoneId)
            : 'Digital / Free Shipping';
        $shipping->save();

        // BD Courier — ফোন অনুযায়ী সফলতার হার অর্ডার লিস্টে দেখাতে (চেকআউট রেসপন্স ব্লক না করে রিকোয়েস্ট শেষ হওয়ার পর রান)
        $fraudSyncPhone = trim((string) ($shipping->phone ?? ''));
        if ($fraudSyncPhone !== '' && config('services.bdcourier.api_key')) {
            dispatch(static function () use ($fraudSyncPhone): void {
                BdCourierService::fetchAndSyncOrders($fraudSyncPhone);
            })->afterResponse();
        }

        // Payment info
        $payment = new Payment();
        $payment->order_id       = $order->id;
        $payment->customer_id    = $customer_id;
        $payment->payment_method = $request->payment_method;

        // =========================================================
        // ⭐ ফিক্সড লজিক: ডাটাবেসে কত টাকা সেভ করব?
        // =========================================================
        if (ManualPaymentGateway::usesHostedRedirect($request->payment_method)) {
            // অনলাইন পেমেন্ট: শুরুতে ০ — কলব্যাকে আপডেট হবে
            $payment->amount = 0;
        } elseif ($manualPayId !== null) {
            $payment->amount                    = 0;
            $payment->manual_payable_snapshot   = max(0, (int) round($payable_amount));
            $payment->trx_id                   = $request->manual_trx_id;
            $payment->sender_number            = $request->filled('manual_sender_number') ? $request->manual_sender_number : null;
        } else {
            // COD
            $payment->amount = $payable_amount;
        }

        $payment->payment_status = 'pending';
        $payment->save();

        // Order details save
        OrderHelper::saveOrderDetails($order);

        // Reserve stock (ledger + reserved counter; products.stock cache synced by the
        // service). Cannot fail here: assertAvailable() above still holds the row locks.
        try {
            \App\Services\InventoryService::reserveForOrder($order, strict: true);
            \Illuminate\Support\Facades\DB::commit();
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            \Log::error('Checkout reservation failed for order draft: '.$e->getMessage());
            Toastr::error('দুঃখিত! স্টক সংক্রান্ত সমস্যার কারণে অর্ডারটি সম্পন্ন করা যায়নি।', 'Failed!');
            return redirect()->route('checkout')->withInput();
        }

        // === Customer SMS ===
        try {
            $customerPhone = isset($shipping) && $shipping->phone ? $shipping->phone : ($request->phone ?? ($order->customer->phone ?? null));
            $customerName  = isset($shipping) && $shipping->name ? $shipping->name : ($request->name ?? ($order->customer->name ?? 'Customer'));
            $site_setting  = GeneralSetting::where('status', 1)->first();
            if ($customerPhone) {
                SmsHelper::send($customerPhone, "প্রিয় {$customerName}! আপনার অর্ডার #{$order->invoice_id} সফলভাবে গ্রহণ করা হয়েছে। মোট: {$order->amount} Tk. {$site_setting->name}", ['order' => 1]);
            }
        } catch (\Exception $e) {
            \Log::error("Customer SMS error for order {$order->id}: " . $e->getMessage());
        }

        // === Admin SMS ===
        try {
            $site_setting  = isset($site_setting) ? $site_setting : GeneralSetting::where('status', 1)->first();
            $customerName  = $request->name ?? ($order->customer->name ?? 'Customer');
            $customerPhone = $request->phone ?? ($order->customer->phone ?? '');
            SmsHelper::sendToAdmin("নতুন অর্ডার!\nOrder#: {$order->invoice_id}\nকাস্টমার: {$customerName}\nমোবাইল: {$customerPhone}\nমোট: {$order->amount} Tk — {$site_setting->name}");
        } catch (\Exception $e) {
            \Log::error('Admin SMS send failed: ' . $e->getMessage());
        }

        // === Admin Order Email Notification (non-blocking — response পাঠানোর পর send হবে) ===
        try {
            $adminEmail = null;

            $site_setting = isset($site_setting) ? $site_setting : GeneralSetting::where('status', 1)->first();
            if (!empty($site_setting->email)) {
                $adminEmail = $site_setting->email;
            }
            if (empty($adminEmail)) {
                $contact = isset($contact) ? $contact : Contact::first();
                $adminEmail = $contact->email ?? null;
            }
            if (empty($adminEmail)) {
                $adminEmail = env('MAIL_FROM_ADDRESS');
            }

            if ($adminEmail) {
                $orderId    = $order->id;
                $invoiceId  = $order->invoice_id;
                $emailTo    = $adminEmail;

                // response পাঠানোর পর run হবে — user কোনো delay দেখবে না
                app()->terminating(function () use ($orderId, $invoiceId, $emailTo) {
                    try {
                        $freshOrder = \App\Models\Order::find($orderId);
                        if ($freshOrder) {
                            \Mail::to($emailTo)->send(new \App\Mail\OrderPlace($freshOrder));
                            \Log::info("Admin order email sent to {$emailTo} for order #{$invoiceId}");
                        }
                    } catch (\Exception $e) {
                        \Log::error("Admin order email failed for order #{$invoiceId}: " . $e->getMessage());
                    }
                });
            }
        } catch (\Exception $e) {
            \Log::error('Admin order email setup failed: ' . $e->getMessage());
        }

        // Incomplete order delete
        IncompleteOrder::where('phone', $request->phone)->delete();

        // =========================================================
        // ⭐ পেমেন্ট গেটওয়ে রিডাইরেক্ট (FIXED)
        // =========================================================

        // Bkash এবং UddoktaPay এর জন্য সেশনে এমাউন্ট সেট করে দিচ্ছি
        // যাতে ওই কন্ট্রোলারগুলো সঠিক এমাউন্ট পায়
        Session::put('payable_amount', $payable_amount);

        if($request->payment_method == 'bkash'){
            Session::forget('coupon_code');
            Session::forget('discount');
            return redirect('/bkash/checkout-url/create?order_id='.$order->id);

        }
        // elseif($request->payment_method == 'shurjopay'){

        //     $info = [
        //         'currency'        => "BDT",
        //         'amount'          => $payable_amount, // সবসময় পূর্ণ গ্র্যান্ড টোটাল (Advance Payment বাতিল)
        //         'order_id'        => uniqid(),
        //         'client_ip'       => $request->ip(),
        //         'customer_name'   => $request->name,
        //         'customer_phone'  => $request->phone,
        //         'email'           => "customer@gmail.com",
        //         'customer_address'=> $request->address,
        //         'customer_city'   => $locationLabelForGateway,
        //         'customer_country'=> "BD",
        //         'value1'          => $order->id
        //     ];

        //     Session::forget('coupon_code');
        //     Session::forget('discount');

        //     $sp = new ShurjopayController();
        //     return $sp->checkout($info);

        // }
        elseif($request->payment_method == 'uddoktapay'){
            Session::forget('coupon_code');
            Session::forget('discount');
            return redirect()->route('uddoktapay.checkout',['order_id'=>$order->id]);

        } elseif($request->payment_method == 'aamarpay'){
            Session::forget('coupon_code');
            Session::forget('discount');
            return redirect()->route('aamarpay.checkout',['order_id'=>$order->id]);

        } elseif ($request->payment_method === 'cod'
            || ManualPaymentGateway::isManualPaymentMethod($request->payment_method)) {
            // Cash On Delivery বা ম্যানুয়াল গাইডেড পেমেন্ট — সরাসরি সাফল্য পেইজ
            $this->createDigitalDownloads($order);

            // Send Facebook Purchase event for COD orders (async - don't block order submission)
            try {
                $order->loadMissing(['shipping', 'customer', 'orderdetails']);
                $capiUser = \App\Support\EcommerceTrackingUser::fromOrder($order);
                if (empty($capiUser['phone']) && $request->phone) {
                    $capiUser['phone'] = $request->phone;
                }
                if (empty($capiUser['name']) && $request->name) {
                    $capiUser['name'] = $request->name;
                }
                $capiUser = \App\Support\EcommerceTrackingUser::forCapi(
                    $capiUser,
                    $_COOKIE['_fbp'] ?? null,
                    $_COOKIE['_fbc'] ?? null
                );

                register_shutdown_function(function () use ($order, $capiUser, $request) {
                    try {
                        $orderDetails = $order->orderdetails ?? \App\Models\Order::with('orderdetails')->find($order->id)?->orderdetails ?? collect();
                        $contentIds  = $orderDetails->pluck('product_id')->map(fn ($id) => (string) $id)->values()->toArray();
                        $contents    = $orderDetails->map(fn ($i) => [
                            'id'         => (string) $i->product_id,
                            'quantity'   => (int) $i->qty,
                            'item_price' => (float) $i->sale_price,
                        ])->values()->toArray();
                        app(\App\Services\FacebookCapiService::class)->sendEvent('Purchase', [
                            'currency'     => 'BDT',
                            'value'        => $order->amount,
                            'order_id'     => $order->invoice_id ?? $order->id,
                            'content_ids'  => $contentIds,
                            'contents'     => $contents,
                            'num_items'    => count($contents),
                            'content_type' => 'product',
                        ], $capiUser, [
                            'event_id'         => 'purchase_'.($order->invoice_id ?? $order->id),
                            'event_source_url' => url('customer/order-success/'.$order->id),
                        ]);
                    } catch (\Exception $e) {
                        \Log::error('Facebook CAPI Purchase event failed for order '.$order->id.': '.$e->getMessage());
                    }
                });
            } catch (\Exception $e) {
                \Log::error('Facebook CAPI setup failed for order '.$order->id.': '.$e->getMessage());
            }

            Session::forget('coupon_code');
            Session::forget('discount');
            return redirect('customer/order-success/'.$order->id);

        } else {
            Toastr::error('পেমেন্ট মেথড সঠিক নয়।', 'Failed!');
            return redirect()->back();
        }
    }

    public function checkout_resend_otp(Request $request)
    {
        $request->validate(['phone' => 'required|string']);

        return $this->checkoutOtpResendForChannel($request, 'customer');
    }


    public function orders(Request $request)
    {
        // Tab -> real order_status id mapping (see order_statuses table)
        // Confirmed  = Completed(6)
        // Processing = Pending(1), Processing(2), On The Way(3), In Courier(5), Unpaid(8)
        // Cancelled  = Cancelled(11)
        $statusMap = [
            'confirmed'  => ['6'],
            'processing' => ['1', '2', '3', '5', '8'],
            'cancelled'  => ['11'],
        ];

        $activeTab = $request->query('status', 'all');

        $query = Order::where('customer_id', Auth::guard('customer')->user()->id)
            ->with(['status', 'orderdetails.product.image', 'orderdetails.image']);

        if ($activeTab !== 'all' && isset($statusMap[$activeTab])) {
            $query->whereIn('order_status', $statusMap[$activeTab]);
        }

        $orders = $query->latest()->paginate(10)->withQueryString();

        return view('frontEnd.layouts.customer.orders', compact('orders', 'activeTab'));
    }

    public function rewards(Request $request)
    {
        $customerId = Auth::guard('customer')->id();
        $filter     = $request->query('filter', 'all');

        $query = \App\Models\RewardPointTransaction::where('customer_id', $customerId)->latest('id');
        if ($filter === 'earned') {
            $query->whereIn('type', ['earned', 'refunded']);   // credits
        } elseif ($filter === 'spent') {
            $query->whereIn('type', ['spent', 'reversed']);    // debits
        } else {
            $filter = 'all';
        }

        $rewardPoints  = \App\Services\RewardPointService::balance($customerId);
        $transactions  = $query->paginate(15)->withQueryString();

        return view('frontEnd.layouts.customer.rewards', compact('rewardPoints', 'transactions', 'filter'));
    }

    /**
     * Checkout "Use Your Reward Point" toggle — returns backend-calculated
     * numbers only; the browser never decides the discount. Auth enforced by
     * the constructor's customer middleware (method not in the except list).
     */
    public function checkout_reward_preview(Request $request)
    {
        $customerId = Auth::guard('customer')->id();

        $subtotal = (float) str_replace([',', '.00'], '', Cart::instance('shopping')->subtotal());
        $discount = (float) Session::get('discount', 0);
        $shipping = (float) Session::get('shipping', 0);
        if (\App\Http\Controllers\Frontend\ShoppingController::hasAllFreeDeliveryProducts()) {
            $shipping = 0;
        }

        $available  = \App\Services\RewardPointService::balance($customerId);
        $pointsUsed = 0;
        if ($request->boolean('use_reward_points')) {
            $pointsUsed = \App\Services\RewardPointService::maxRedeemable($customerId, max(0, $subtotal - $discount));
        }
        $rewardDiscount = $pointsUsed * max(1, (int) config('rewards.point_value', 1));
        $total          = max(0, $subtotal + $shipping - $discount - $rewardDiscount);

        return response()->json([
            'available_points' => $available,
            'points_used'      => $pointsUsed,
            'reward_discount'  => $rewardDiscount,
            'subtotal'         => $subtotal,
            'delivery_charge'  => $shipping,
            'discount'         => $discount,
            'total'            => $total,
            // What this order would earn once delivered (points don't earn on points).
            'earn_points'      => \App\Services\RewardPointService::earnedPointsFor(max(0, $subtotal - $discount - $rewardDiscount)),
        ]);
    }

    // ============================
    // Saved addresses (Addresses page)
    // ============================

    /** Customer wishlist page — table of saved products + recommended products. */
    public function wishlist()
    {
        $customerId = Auth::guard('customer')->id();

        $wishlistItems = \App\Models\Wishlist::with([
                'product' => fn ($q) => $q->with(['image', 'subcategory', 'prosizes', 'procolors']),
            ])
            ->where('customer_id', $customerId)
            ->latest('id')
            ->get()
            ->filter(fn ($w) => $w->product !== null); // product deleted since → skip row

        $wishlistedIds = $wishlistItems->pluck('product_id')->all();

        // Same source/shape as the cart page's recommended section, minus already-wishlisted items.
        $recommendedProducts = Product::where(['status' => 1, 'approval_status' => 'approved'])
            ->whereNotIn('id', $wishlistedIds ?: [0])
            ->with(['image', 'category', 'reviews', 'prosizes', 'procolors'])
            ->latest('id')
            ->take(4)
            ->get();

        return view('frontEnd.layouts.customer.wishlist', compact('wishlistItems', 'recommendedProducts'));
    }

    /**
     * Toggle a product in the wishlist (AJAX). Guest-safe: returns 401 JSON so the
     * frontend can show a login prompt instead of being redirected by middleware.
     * customer_id always comes from the session — never from the request.
     */
    public function wishlist_toggle(Request $request)
    {
        if (!Auth::guard('customer')->check()) {
            return response()->json([
                'success' => false,
                'login_required' => true,
                'message' => 'Please login to add products to your wishlist.',
            ], 401);
        }

        $request->validate(['product_id' => 'required|integer|exists:products,id']);

        $customerId = Auth::guard('customer')->id();
        $productId  = (int) $request->product_id;

        $existing = \App\Models\Wishlist::where('customer_id', $customerId)
            ->where('product_id', $productId)
            ->first();

        if ($existing) {
            $existing->delete();

            return response()->json([
                'success' => true,
                'in_wishlist' => false,
                'message' => 'Product removed from wishlist.',
            ]);
        }

        // firstOrCreate + the UNIQUE(customer_id, product_id) index make double
        // submits harmless (a race just lands on the existing row).
        \App\Models\Wishlist::firstOrCreate([
            'customer_id' => $customerId,
            'product_id'  => $productId,
        ]);

        return response()->json([
            'success' => true,
            'in_wishlist' => true,
            'message' => 'Product added to wishlist.',
        ]);
    }

    public function addresses()
    {
        $addresses = \App\Models\CustomerAddress::with(['district:id,name', 'zone:id,name,name_bn'])
            ->where('customer_id', Auth::guard('customer')->user()->id)
            ->orderByDesc('is_default')
            ->orderBy('id')
            ->get();

        return view('frontEnd.layouts.customer.addresses', compact('addresses'));
    }

    /**
     * Validate the reusable address-form-modal input (adr_* field names avoid
     * colliding with the checkout form's old() values) and map to model columns.
     * Returns mapped data or a redirect-back response on zone/district mismatch.
     */
    private function validateAddressForm(Request $request): array
    {
        $request->validate([
            'adr_name'        => 'required|string|max:155',
            'adr_phone'       => 'required|string|max:55',
            'adr_email'       => 'nullable|email|max:155',
            'adr_post_code'   => 'nullable|string|max:20',
            'adr_district_id' => 'required|integer|exists:districts,id',
            'adr_zone_id'     => 'required|integer',
            'adr_address'     => 'required|string|max:1000',
        ]);

        return [
            'name'        => $request->adr_name,
            'phone'       => $request->adr_phone,
            'email'       => $request->adr_email,
            'post_code'   => $request->adr_post_code,
            'district_id' => (int) $request->adr_district_id,
            'zone_id'     => (int) $request->adr_zone_id,
            'address'     => $request->adr_address,
        ];
    }

    public function address_store(Request $request)
    {
        $data = $this->validateAddressForm($request);

        // Zone must belong to the selected district (never trust arbitrary zone_id).
        if (! $this->zoneBelongsToDistrict($data['zone_id'], $data['district_id'])) {
            return back()->withInput()->withErrors(['adr_zone_id' => 'Selected zone does not belong to the selected district.']);
        }

        $data['division_id'] = \App\Models\DeliveryDistrict::where('id', $data['district_id'])->value('division_id');

        $customerId = Auth::guard('customer')->user()->id;
        $data['customer_id'] = $customerId;

        // First saved address automatically becomes the default.
        $data['is_default'] = !\App\Models\CustomerAddress::where('customer_id', $customerId)->exists();

        \App\Models\CustomerAddress::create($data);

        Toastr::success('Address saved successfully', 'Success!');
        return redirect()->back();
    }

    public function address_update(Request $request, $id)
    {
        $address = \App\Models\CustomerAddress::where('id', $id)
            ->where('customer_id', Auth::guard('customer')->user()->id)
            ->firstOrFail();

        $data = $this->validateAddressForm($request);

        if (! $this->zoneBelongsToDistrict($data['zone_id'], $data['district_id'])) {
            return back()->withInput()->withErrors(['adr_zone_id' => 'Selected zone does not belong to the selected district.']);
        }

        $data['division_id'] = \App\Models\DeliveryDistrict::where('id', $data['district_id'])->value('division_id');

        // is_default is intentionally untouched here — changed only via address_default().
        $address->update($data);

        Toastr::success('Address updated successfully', 'Success!');
        return redirect()->back();
    }

    private function zoneBelongsToDistrict(int $zoneId, int $districtId): bool
    {
        return \App\Models\DeliveryZone::where('id', $zoneId)
            ->where('district_id', $districtId)
            ->where('status', 1)
            ->exists();
    }

    // Active zones of a district for the address-form-modal (AJAX).
    public function delivery_zones(Request $request)
    {
        $request->validate(['district_id' => 'required|integer']);

        $zones = \App\Models\DeliveryZone::where('district_id', $request->district_id)
            ->where('status', 1)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'name_bn']);

        return response()->json(['data' => $zones]);
    }

    public function address_delete($id)
    {
        $address = \App\Models\CustomerAddress::where('id', $id)
            ->where('customer_id', Auth::guard('customer')->user()->id)
            ->firstOrFail();

        $address->delete();

        Toastr::success('Address deleted', 'Success!');
        return redirect()->route('customer.addresses');
    }

    public function address_default(Request $request)
    {
        $request->validate(['address_id' => 'required|integer']);

        $customerId = Auth::guard('customer')->user()->id;

        $address = \App\Models\CustomerAddress::where('id', $request->address_id)
            ->where('customer_id', $customerId)
            ->firstOrFail();

        // Only one default per customer.
        \App\Models\CustomerAddress::where('customer_id', $customerId)->update(['is_default' => false]);
        $address->update(['is_default' => true]);

        Toastr::success('Default address updated', 'Success!');
        return redirect()->route('customer.addresses');
    }

    public function order_details($id)
    {
        $customerId = Auth::guard('customer')->user()->id;

        // Authorization: customer can only view their OWN order.
        $order = Order::where('id', $id)
            ->where('customer_id', $customerId)
            ->with([
                'status',
                'shipping',
                'payment',
                'orderdetails.product.image',
                'orderdetails.product.category',
                'orderdetails.product.subcategory',
                'orderdetails.image',
            ])
            ->first();

        if (!$order) {
            abort(403, 'You are not authorized to view this order.');
        }

        // Recent orders for the top order-selector dropdown.
        $recentOrders = Order::where('customer_id', $customerId)
            ->latest()
            ->take(15)
            ->get(['id', 'invoice_id', 'created_at']);

        return view('frontEnd.layouts.customer.order_details', compact('order', 'recentOrders'));
    }

    public function order_success($id)
    {
        $order = Order::with(['orderdetails.size', 'orderdetails.color', 'shipping'])
            ->where('id', $id)
            ->firstOrFail();
        return view('frontEnd.layouts.customer.order_success', compact('order'));
    }

    public function invoice(Request $request)
    {
        $order = Order::where([
                'id'=>$request->id,
                'customer_id'=>Auth::guard('customer')->user()->id
            ])
            ->with(['orderdetails.size', 'orderdetails.color', 'payment', 'shipping.zone', 'shipping.district', 'customer'])
            ->firstOrFail();

        return view('frontEnd.layouts.customer.invoice',compact('order'));
    }

    public function order_note(Request $request)
    {
        $order = Order::where([
                'id'=>$request->id,
                'customer_id'=>Auth::guard('customer')->user()->id
            ])->firstOrFail();

        return view('frontEnd.layouts.customer.order_note',compact('order'));
    }

    public function profile_edit(Request $request)
    {
        $profile_edit = Customer::where(['id'=>Auth::guard('customer')->user()->id])->firstOrFail();

        // District → Zone (same source as the Add/Edit Address flow).
        // The legacy district-name + area (legacy_district_areas) pair is no longer
        // rendered here; those columns stay untouched in the database.
        $districts = \App\Models\DeliveryDistrict::active()->ordered()->get(['id', 'name']);

        // Refresh the model to get latest data
        $profile_edit->refresh();

        return view('frontEnd.layouts.customer.profile_edit',compact('profile_edit','districts'));
    }

    public function profile_update(Request $request)
    {
        $update_data = Customer::where(['id'=>Auth::guard('customer')->user()->id])->firstOrFail();

        // Validation — District → Zone replaces the legacy district-name + area pair.
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email|max:255|unique:customers,email,'.$update_data->id,
            'address' => 'required|string|max:500',
            'district_id' => 'required|integer|exists:districts,id',
            'zone_id' => 'required|integer|exists:delivery_zones,id',
            'image' => 'nullable|image|mimes:jpeg,jpg,png,webp|max:2048',
        ]);

        // The selected zone must actually belong to the selected district (and be active).
        if (!$this->zoneBelongsToDistrict((int) $request->zone_id, (int) $request->district_id)) {
            Toastr::error('Selected zone does not belong to the selected district.', 'Error!');
            return redirect()->back()->withInput();
        }

        $image = $request->file('image');
        if($image){
            try {
                // Delete old image if exists. Stored paths are web paths ("public/uploads/...")
                // while public_path() already points at the public/ directory — strip the prefix.
                if ($update_data->image) {
                    $oldImagePath = public_path(preg_replace('#^public/#', '', $update_data->image));
                    if (file_exists($oldImagePath)) {
                        @unlink($oldImagePath);
                    }
                }

                // Slug the base name only: Str::slug() strips the dot, so slugging the whole
                // filename produced extension-less files ("photojpg") that browsers won't render.
                $name = time().'-'.Str::slug(pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME)).'.webp';
                $name = strtolower($name);

                // Web path stored in DB; asset() prefixes the app URL. On disk it lives under
                // public/, so public_path() must NOT repeat the "public/" segment.
                $uploadpath = 'public/uploads/customer/';
                $uploadFullPath = public_path('uploads/customer/');

                // Create directory if not exists
                if (!file_exists($uploadFullPath)) {
                    \Illuminate\Support\Facades\File::makeDirectory($uploadFullPath, 0755, true);
                }

                // Full path for saving
                $imageUrl = $uploadFullPath . $name;

                // Process and save image
                $img = Image::make($image->getRealPath());
                $img->encode('webp', 90);
                $img->resize(300, 300, function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                });
                $img->save($imageUrl);

                // Verify image was saved
                if (!file_exists($imageUrl)) {
                    throw new \Exception('Image file was not saved successfully');
                }

                // Save path in database (with public/ prefix for asset() helper)
                $imageUrl = $uploadpath . $name;
            } catch (\Exception $e) {
                Toastr::error('Image upload failed: ' . $e->getMessage(), 'Error!');
                return redirect()->back()->withInput();
            }
        }else{
            $imageUrl = $update_data->image;
        }

        // Only the authenticated customer's row is touched ($update_data was resolved
        // from the guard, never from request input). Legacy district/area columns are
        // left as-is so old data survives.
        $update_data->name        = $request->name;
        $update_data->phone       = $request->phone;
        $update_data->email       = $request->email;
        $update_data->address     = $request->address;
        $update_data->district_id = (int) $request->district_id;
        $update_data->zone_id     = (int) $request->zone_id;
        $update_data->image       = $imageUrl;
        $update_data->save();

        // Refresh the model to get updated attributes
        $update_data->refresh();

        Toastr::success('আপনার প্রোফাইল সফলভাবে আপডেট হয়েছে', 'সফল!');
        return redirect()->route('customer.profile_edit');
    }

   public function order_track_result(Request $request)
    {
        $phone = $request->phone;
        $invoice_id = $request->invoice_id;

        // ১. ভ্যালিডেশন: অন্তত একটি ইনপুট থাকতে হবে
        if (!$phone && !$invoice_id) {
            Toastr::error('অনুগ্রহ করে মোবাইল নাম্বার অথবা ইনভয়েস আইডি দিন', 'Error');
            return redirect()->back();
        }

        // ২. কুয়েরি শুরু (Order মডেল ব্যবহার করে)
        $query = Order::query();

        // যদি ইনভয়েস আইডি দেওয়া থাকে
        if ($invoice_id) {
            $query->where('invoice_id', $invoice_id);
        }

        // যদি ফোন নম্বর দেওয়া থাকে
        if ($phone) {
            // আমরা Shipping টেবিল চেক করব কারণ অর্ডারের ফোন নম্বর সেখানেই থাকে
            $query->whereHas('shipping', function($q) use ($phone){
                $q->where('phone', $phone);
            });
        }

        // ৩. ডাটা নিয়ে আসা (Eager Loading সহ)
        // latest() দিলে নতুন অর্ডার আগে দেখাবে
        $order = $query->with(['shipping', 'status', 'orderdetails'])->latest()->get();

        // ৪. যদি কোনো অর্ডার না পাওয়া যায়
        if ($order->count() == 0) {
            Toastr::error('দুঃখিত! কোনো অর্ডার পাওয়া যায়নি।', 'Failed');
            return redirect()->back();
        }

        // ৫. ভিউতে ডাটা পাঠানো
        // আপনার কন্ট্রোলারে ভিউয়ের নাম 'tracking_result' দেওয়া আছে, তাই সেটিই রাখলাম।
        // কিন্তু নিশ্চিত হোন আপনার ব্লেড ফাইলের নাম tracking_result.blade.php নাকি track_order.blade.php
        return view('frontEnd.layouts.customer.tracking_result', compact('order'));
    }
// এই ফাংশনটি মিসিং থাকার কারণেই এরর আসছিল
    public function order_track()
    {
        return view('frontEnd.layouts.customer.order_track');
    }
    public function change_pass()
    {
        return view('frontEnd.layouts.customer.change_password');
    }

    public function password_update(Request $request)
    {
        $this->validate($request, [
            'old_password'=>'required',
            'new_password'=>'required',
            'confirm_password' => 'required_with:new_password|same:new_password|'
        ]);

        $customer = Customer::find(Auth::guard('customer')->user()->id);
        $hashPass = $customer->password;

        if (Hash::check($request->old_password, $hashPass)) {
            $customer->fill([
                'password' => Hash::make($request->new_password)
            ])->save();

            Toastr::success('Success', 'Password changed successfully!');
            return redirect()->route('customer.account');
        }else{
            Toastr::error('Failed', 'Old password not match!');
            return redirect()->back();
        }
    }

    // =====================================
    // ⭐ DIGITAL DOWNLOAD CREATOR (HELPER)
    // =====================================
    private function createDigitalDownloads(Order $order)
    {
        // orderdetails থেকে product_id নিয়ে Product লোড করব
        $items = OrderDetails::where('order_id', $order->id)->get();

        foreach ($items as $item) {
            $product = Product::find($item->product_id);

            if ($product && $product->is_digital == 1 && $product->digital_file) {

                // একই order+product+customer এর জন্য ডুপ্লিকেট না হয়
                DigitalDownload::firstOrCreate(
                    [
                        'order_id'    => $order->id,
                        'product_id'  => $product->id,
                        'customer_id' => $order->customer_id,
                    ],
                    [
                        'token'               => Str::uuid(),
                        'file_path'           => $product->digital_file,
                        'remaining_downloads' => $product->download_limit ?? 5,
                        'expires_at'          => $product->download_expire_days
                                                    ? now()->addDays($product->download_expire_days)
                                                    : null,
                    ]
                );
            }
        }
    }
}
