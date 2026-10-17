{{--
    Admin sidebar menu, grouped into sections.

    To move an item to another section, cut its block (from its @can to its @endcan) and paste
    it inside another <x-admin.menu-group>. A section label only shows when the admin can see at
    least one of its items. Collapse state, badges on collapsed sections and "Search menu…" are
    handled by public/backEnd/assets/js/admin-sidebar.js; styles in admin-sidebar.css.

    $on(...routes) marks an item active on all of its pages (e.g. a product's edit page), not only
    on the exact link URLs the theme matches.
--}}
@php
    $on = fn (string ...$routes) => request()->routeIs(...$routes);
    $pending_reviews = \App\Models\Review::where('status', 'pending')->count();
@endphp

<div id="sidebar-menu" data-admin-id="{{ \Illuminate\Support\Facades\Auth::guard('admin')->id() }}">
  <div class="bg-menu-search" role="search">
    <i class="fe-search" aria-hidden="true"></i>
    <input type="search" id="bg-menu-search" placeholder="Search menu…" autocomplete="off" spellcheck="false"
           aria-label="Search menu" aria-keyshortcuts="/">
    <kbd aria-hidden="true">/</kbd>
  </div>

  <ul id="side-menu">
@can('dashboard-view')
<li @class(['menuitem-active' => $on('admin.dashboard')])>
  <a href="{{ url('admin/dashboard') }}">
    <i data-feather="airplay"></i>
    <span> Dashboard </span>
  </a>
</li>
@endcan

{{-- ============================== SALES ============================== --}}
<x-admin.menu-group group="sales" label="Sales">

{{-- Orders --}}
@canany(['order-list', 'order-edit', 'order-create'])
@php $act = $on('admin.orders', 'admin.orders.*', 'admin.order.edit', 'admin.order.invoice', 'admin.order.process', 'admin.manual_orders.*', 'admin.incomplete-orders.*', 'admin.reseller-orders.*', 'orderstatus.*', 'customers.ip_block', 'customers.ipblock.*'); @endphp
<li @class(['menuitem-active' => $act])>
  <a href="#sidebar-orders" data-bs-toggle="collapse">
    <i data-feather="shopping-cart"></i>
    <span> Orders </span>
    <span class="menu-arrow"></span>
  </a>
  <div @class(['collapse', 'show' => $act]) id="sidebar-orders">
    <ul class="nav-second-level">
      @can('order-list')
      <li><a href="{{ route('admin.orders', ['slug'=>'all']) }}"><i data-feather="file-plus"></i> All Order</a></li>
      <li><a href="{{ route('admin.manual_orders.index') }}"><i data-feather="file-text"></i> Manual Orders</a></li>
      <li><a href="{{ route('admin.manual_orders.create') }}"><i data-feather="edit-3"></i> Create Manual Invoice</a></li>
      @can('order-list')
      <li>
        <a href="{{ route('admin.reseller-orders.index') }}"><i data-feather="users"></i> Reseller Orders</a>
      </li>
      @endcan
      <li><a href="{{ route('admin.incomplete-orders.index') }}"><i data-feather="file-plus"></i> Incomplete Orders</a></li>
      @foreach($orderstatus as $value)
        <li><a href="{{ route('admin.orders', ['slug'=>$value->slug]) }}"><i data-feather="file-plus"></i>{{ $value->name }}</a></li>
      @endforeach
      @endcan
      @can('order-edit')
      <li><a href="{{ route('orderstatus.index') }}"><i data-feather="file-plus"></i> Order Status</a></li>
      @endcan
      @can('order-manage')
      <li>
        <a href="{{route('customers.ip_block')}}"><i data-feather="file-plus"></i> IP Block</a>
      </li>
      @endcan
    </ul>
  </div>
</li>
@endcanany

{{-- POS System --}}
@can('order-create')
<li @class(['menuitem-active' => $on('admin.order.create')])>
  <a href="{{route('admin.order.create')}}">
    <i data-feather="cpu"></i>
    <span>POS System</span>
  </a>
</li>
@endcan

{{-- Refunds --}}
@canany(['order-list', 'order-edit'])
@php $act = $on('admin.refunds.*'); @endphp
<li @class(['menuitem-active' => $act])>
  <a href="#sidebar-refunds" data-bs-toggle="collapse">
    <i data-feather="rotate-ccw"></i>
    <span> Refunds </span>
    <span class="menu-arrow"></span>
  </a>
  <div @class(['collapse', 'show' => $act]) id="sidebar-refunds">
    <ul class="nav-second-level">
      <li><a href="{{ route('admin.refunds.index') }}"><i data-feather="list"></i> All Refunds</a></li>
      <li><a href="{{ route('admin.refunds.index', ['status' => 'pending']) }}"><i data-feather="clock"></i> Pending Refunds</a></li>
      <li><a href="{{ route('admin.refunds.index', ['status' => 'approved']) }}"><i data-feather="check-circle"></i> Approved Refunds</a></li>
      <li><a href="{{ route('admin.refunds.index', ['status' => 'processed']) }}"><i data-feather="check"></i> Processed Refunds</a></li>
    </ul>
  </div>
</li>
@endcanany

{{-- Delivery --}}
@canany(['shipping-list', 'shipping-create', 'shipping-edit', 'delivery-boy-list', 'delivery-withdrawal-list', 'delivery-location-list'])
@php $act = $on('admin.delivery.*', 'admin.delivery-boys.*'); @endphp
<li @class(['menuitem-active' => $act])>
  <a href="#sidebar-delivery-module" data-bs-toggle="collapse">
    <i data-feather="truck"></i>
    <span>Delivery</span>
    <span class="menu-arrow"></span>
  </a>
  <div @class(['collapse', 'show' => $act]) id="sidebar-delivery-module">
    <ul class="nav-second-level">
      @can('delivery-boy-list')
      <li><a href="{{ route('admin.delivery-boys.index') }}"><i data-feather="users"></i> Delivery persons</a></li>
      @endcan
      @can('delivery-withdrawal-list')
      <li><a href="{{ route('admin.delivery-boys.withdrawals') }}"><i data-feather="dollar-sign"></i> Rider withdrawals</a></li>
      @endcan
      @can('delivery-location-list')
      <li><a href="{{ route('admin.delivery.divisions.index') }}"><i data-feather="map-pin"></i> Delivery locations <span class="text-muted" style="font-size:.75rem;">(Division → District → Thana)</span></a></li>
      @endcan
    </ul>
  </div>
</li>
@endcanany

{{-- Vendors (switched off in config/business.php) --}}
@if(config('business.vendor_enabled'))
@canany(['vendor-list', 'vendor-create', 'vendor-edit', 'vendor-verification', 'vendor-withdrawal'])
@php
  $pendingVerificationCount = \App\Models\Vendor::where('verification_status', 'pending')->count();
  $act = $on('admin.vendors.*', 'admin.vendor.verification.*', 'admin.vendor.withdrawals.*');
@endphp
<li @class(['menuitem-active' => $act])>
  <a href="#sidebar-vendors" data-bs-toggle="collapse">
    <i data-feather="users"></i>
    <span> Vendors </span>
    <span class="menu-arrow"></span>
  </a>
  <div @class(['collapse', 'show' => $act]) id="sidebar-vendors">
    <ul class="nav-second-level">
      @can('vendor-list')
      <li><a href="{{ route('admin.vendors.index') }}"><i data-feather="file-plus"></i> All Vendors</a></li>
      @endcan
      @can('vendor-verification')
      <li>
        <a href="{{ route('admin.vendor.verification.index') }}">
          <i data-feather="shield"></i> Vendor Verifications
          @if($pendingVerificationCount > 0)
            <span class="badge bg-danger rounded-pill float-end"> {{ $pendingVerificationCount }}</span>
          @endif
        </a>
      </li>
      @endcan
      @can('vendor-withdrawal')
      <li><a href="{{ route('admin.vendor.withdrawals.index') }}"><i data-feather="dollar-sign"></i> Vendor Withdrawals</a></li>
      @endcan
    </ul>
  </div>
</li>
@endcanany
@endif

{{-- Resellers (switched off in config/business.php) --}}
@if(config('business.reseller_enabled'))
@canany(['reseller-list', 'reseller-create', 'reseller-edit', 'reseller-verification', 'reseller-withdrawal'])
@php
  $pendingResellerVerificationCount = \App\Models\User::where('role', 'reseller')->where('verification_status', 'pending')->count();
  $pendingResellerWithdrawalCount = \App\Models\ResellerWithdrawal::where('status', 'pending')->count();
  $act = $on('admin.resellers.*', 'admin.reseller.verification.*', 'admin.reseller.withdrawals.*', 'admin.reseller-deposits.*');
@endphp
<li @class(['menuitem-active' => $act])>
  <a href="#sidebar-resellers" data-bs-toggle="collapse">
    <i data-feather="user-check"></i>
    <span> Resellers </span>
    <span class="menu-arrow"></span>
  </a>
  <div @class(['collapse', 'show' => $act]) id="sidebar-resellers">
    <ul class="nav-second-level">
      @can('reseller-list')
      <li><a href="{{ route('admin.resellers.index') }}"><i data-feather="file-plus"></i> All Resellers</a></li>
      @endcan
      @can('reseller-withdrawal')
      <li>
        <a href="{{ route('admin.reseller-deposits.index') }}">
          <i data-feather="credit-card"></i> Reseller Deposits
          @php $pendingDepositCount = \App\Models\ResellerDeposit::where('status', 'pending')->count(); @endphp
          @if($pendingDepositCount > 0)
            <span class="badge bg-warning rounded-pill float-end">{{ $pendingDepositCount }}</span>
          @endif
        </a>
      </li>
      @endcan
      @can('reseller-verification')
      <li>
        <a href="{{ route('admin.reseller.verification.index') }}">
          <i data-feather="shield"></i> Verifications
          @if($pendingResellerVerificationCount > 0)
            <span class="badge bg-danger rounded-pill float-end">{{ $pendingResellerVerificationCount }}</span>
          @endif
        </a>
      </li>
      @endcan
      @can('reseller-withdrawal')
      <li>
        <a href="{{ route('admin.reseller.withdrawals.index') }}">
          <i data-feather="dollar-sign"></i> Withdrawals
          @if($pendingResellerWithdrawalCount > 0)
            <span class="badge bg-warning rounded-pill float-end">{{ $pendingResellerWithdrawalCount }}</span>
          @endif
        </a>
      </li>
      @endcan
    </ul>
  </div>
</li>
@endcanany
@endif

</x-admin.menu-group>

{{-- ============================== PRODUCTS & STOCK ============================== --}}
<x-admin.menu-group group="products" label="Products & Stock">

{{-- Products --}}
@canany(['product-list', 'category-list', 'subcategory-list', 'childcategory-list'])
@php $act = $on('products.*', 'inhouse.products.*', 'admin.products.*', 'admin.wholesale_products.*', 'categories.*', 'subcategories.*', 'childcategories.*', 'brands.*', 'colors.*', 'sizes.*', 'weights.*', 'lifestages.*', 'flavors.*'); @endphp
<li @class(['menuitem-active' => $act])>
  <a href="#siebar-product" data-bs-toggle="collapse">
    <i data-feather="database"></i>
    <span> Products </span>
    <span class="menu-arrow"></span>
  </a>
  <div @class(['collapse', 'show' => $act]) id="siebar-product">
    <ul class="nav-second-level">
      @can('product-list')
      <li><a href="{{ route('inhouse.products.index') }}"><i data-feather="package"></i> All Inhouse Products</a></li>
      <li><a href="{{ route('admin.products.wholesale') }}"><i data-feather="layers"></i> Wholesale Products</a></li>
      @endcan
      @can('product-create')
      <li><a href="{{ route('products.create') }}"><i data-feather="plus-circle"></i> Add Product</a></li>
      <li><a href="{{ route('products.import') }}"><i data-feather="upload"></i> Import Products</a></li>
      @endcan
      <li><hr class="dropdown-divider"></li>
      @can('category-list')
      <li><a href="{{ route('categories.index') }}"><i data-feather="file-plus"></i> Categories</a></li>
      @endcan
      @can('subcategory-list')
      <li><a href="{{ route('subcategories.index') }}"><i data-feather="file-plus"></i> Subcategories</a></li>
      @endcan
      @can('childcategory-list')
      <li><a href="{{ route('childcategories.index') }}"><i data-feather="file-plus"></i> Childcategories</a></li>
      @endcan
      @canany(['brand-list', 'brand-create', 'brand-edit'])
      <li><a href="{{ route('brands.index') }}"><i data-feather="file-plus"></i> Brands</a></li>
      @endcanany
      @canany(['color-list', 'color-create', 'color-edit'])
      <li><a href="{{ route('colors.index') }}"><i data-feather="file-plus"></i> Colors</a></li>
      @endcanany
      @canany(['size-list', 'size-create', 'size-edit'])
      <li><a href="{{ route('sizes.index') }}"><i data-feather="file-plus"></i> Sizes</a></li>
      @endcanany
      <li><a href="{{ route('weights.index') }}"><i data-feather="file-plus"></i> Weights</a></li>
      <li><a href="{{ route('lifestages.index') }}"><i data-feather="file-plus"></i> Life Stages</a></li>
      <li><a href="{{ route('flavors.index') }}"><i data-feather="file-plus"></i> Flavors</a></li>
    </ul>
  </div>
</li>
@endcanany

{{-- Kitten Packs --}}
@php $act = $on('admin.kitten-pack.*'); @endphp
<li @class(['menuitem-active' => $act])>
    <a href="#sidebar-kitten-pack" data-bs-toggle="collapse">
        <i data-feather="package"></i>
        <span> Kitten Packs </span>
        <span class="menu-arrow"></span>
    </a>
    <div @class(['collapse', 'show' => $act]) id="sidebar-kitten-pack">
        <ul class="nav-second-level">
            <li>
                <a href="{{ route('admin.kitten-pack.index') }}">
                    <i data-feather="list"></i>
                    All Packs
                </a>
            </li>
            <li>
                <a href="{{ route('admin.kitten-pack.create') }}">
                    <i data-feather="plus-circle"></i>
                    Add New
                </a>
            </li>
        </ul>
    </div>
</li>

{{-- Inventory (badge: products at or below their low-stock threshold) --}}
@php
    $invLowCount = \App\Models\InventoryStock::whereRaw('(on_hand - reserved) <= low_stock_threshold')->count();
    $act = $on('admin.inventory.*');
@endphp
<li @class(['menuitem-active' => $act])>
  <a href="#sidebar-inventory" data-bs-toggle="collapse">
    <i data-feather="box"></i>
    <span> Inventory </span>
    @if($invLowCount > 0)
      <span class="badge bg-danger rounded-pill float-end me-2">{{ $invLowCount }}</span>
    @endif
    <span class="menu-arrow"></span>
  </a>
  <div @class(['collapse', 'show' => $act]) id="sidebar-inventory">
    <ul class="nav-second-level">
      <li><a href="{{ route('admin.inventory.dashboard') }}"><i data-feather="grid"></i> Dashboard</a></li>
      <li><a href="{{ route('admin.inventory.stock') }}"><i data-feather="layers"></i> Stock Overview</a></li>
      <li><a href="{{ route('admin.inventory.movements') }}"><i data-feather="list"></i> Stock Movements</a></li>
      <li><a href="{{ route('admin.inventory.low_stock') }}"><i data-feather="alert-triangle"></i> Low Stock</a></li>
      <li><a href="{{ route('admin.inventory.adjust') }}"><i data-feather="sliders"></i> Stock Adjustments</a></li>
      <li><a href="{{ route('admin.inventory.reports') }}"><i data-feather="bar-chart-2"></i> Reports</a></li>
    </ul>
  </div>
</li>

{{-- Purchases --}}
@canany(['purchase-list', 'purchase-create', 'purchase-edit'])
<li @class(['menuitem-active' => $on('purchases.*')])>
  <a href="{{ route('purchases.index') }}">
    <i data-feather="file-text"></i>
    <span>Purchases</span>
  </a>
</li>
@endcanany

{{-- Suppliers --}}
@canany(['supplier-list', 'supplier-create', 'supplier-edit'])
<li @class(['menuitem-active' => $on('admin.suppliers.*')])>
  <a href="{{ route('admin.suppliers.index') }}">
    <i data-feather="truck"></i>
    <span>Suppliers</span>
  </a>
</li>
@endcanany

</x-admin.menu-group>

{{-- ============================== CUSTOMERS ============================== --}}
<x-admin.menu-group group="customers" label="Customers">

{{-- Customers (was under Users) --}}
@canany(['customer-list', 'customer-create', 'customer-edit'])
<li @class(['menuitem-active' => $on('customers.*') && !$on('customers.ip_block', 'customers.ipblock.*')])>
  <a href="{{ route('customers.index') }}">
    <i data-feather="user-check"></i>
    <span> Customers </span>
  </a>
</li>
@endcanany

{{-- Reviews --}}
@can('review-list')
@php $act = $on('reviews.*'); @endphp
<li @class(['menuitem-active' => $act])>
  <a href="#sidebar-product-review" data-bs-toggle="collapse">
    <i data-feather="star"></i>
    <span> Reviews </span>
    <span class="menu-arrow"></span>
  </a>
  <div @class(['collapse', 'show' => $act]) id="sidebar-product-review">
    <ul class="nav-second-level">
      @can('review-list')
      <li><a href="{{ route('reviews.pending') }}"><i data-feather="file-plus"></i> Pending Reviews ({{ $pending_reviews }})</a></li>
      <li><a href="{{ route('reviews.index') }}"><i data-feather="file-plus"></i> All Reviews</a></li>
      @endcan
      @can('review-create')
      <li><a href="{{ route('reviews.pending') }}"><i data-feather="file-plus"></i> Create</a></li>
      @endcan
    </ul>
  </div>
</li>
@endcan

{{-- Complaints --}}
@canany(['complaint-list', 'complaint-create', 'complaint-edit'])
<li @class(['menuitem-active' => $on('backEnd.complaints.*')])>
    <a href="{{ route('backEnd.complaints.index') }}">
        <i data-feather="alert-circle"></i>
        <span> Complaints </span>
    </a>
</li>
@endcanany

{{-- Contact Messages --}}
@can('contact-list')
<li @class(['menuitem-active' => $on('admin.contact.messages*')])>
    <a href="{{ route('admin.contact.messages') }}">
        <i data-feather="mail"></i>
        <span> Contact Messages </span>
    </a>
</li>
@endcan

{{-- Newsletter Subscribers --}}
@can('newsletter-list')
<li @class(['menuitem-active' => $on('admin.newsletter.subscribers*')])>
    <a href="{{ route('admin.newsletter.subscribers') }}">
        <i data-feather="mail"></i>
        <span> Newsletter Subscribers </span>
    </a>
</li>
@endcan

</x-admin.menu-group>

{{-- ============================== FRAUD PROTECTION ============================== --}}
<x-admin.menu-group group="fraud" label="Fraud Protection">

{{-- Manual Fraud Check --}}
@can('fraud-check')
<li @class(['menuitem-active' => $on('manualFraud.*')])>
  <a href="{{ route('manualFraud.page') }}">
    <i data-feather="search"></i>
    <span>Manual Fraud Check</span>
  </a>
</li>
@endcan

{{-- Order Restriction --}}
@canany(['setting-list', 'setting-edit'])
<li @class(['menuitem-active' => $on('admin.order.restriction.*')])>
  <a href="{{ route('admin.order.restriction.setting.index') }}">
    <i data-feather="clock"></i>
    <span> Order Restriction</span>
  </a>
</li>
@endcanany

{{-- Fraud API Settings --}}
@canany(['fraud-setting-list', 'fraud-setting-edit'])
@php $act = $on('admin.fraud.index', 'admin.fraud.update'); @endphp
<li @class(['menuitem-active' => $act])>
  <a href="#sidebar-fraud" data-bs-toggle="collapse">
    <i data-feather="shield"></i>
    <span> Fraud API Settings </span>
    <span class="menu-arrow"></span>
  </a>
  <div @class(['collapse', 'show' => $act]) id="sidebar-fraud">
    <ul class="nav-second-level">
      @can('fraud-setting-list')
      <li>
        <a href="{{ route('admin.fraud.index') }}">
          <i data-feather="key"></i> Manage Fraud API
        </a>
      </li>
      @endcan
    </ul>
  </div>
</li>
@endcanany

</x-admin.menu-group>

{{-- ============================== MARKETING ============================== --}}
<x-admin.menu-group group="marketing" label="Marketing">

{{-- Coupons --}}
@canany(['coupon-list', 'coupon-create', 'coupon-edit', 'coupon-delete'])
@php $act = $on('admin.coupons.*'); @endphp
<li @class(['menuitem-active' => $act])>
  <a href="#sidebar-coupon" data-bs-toggle="collapse">
    <i data-feather="gift"></i>
    <span> Coupons </span>
    <span class="menu-arrow"></span>
  </a>
  <div @class(['collapse', 'show' => $act]) id="sidebar-coupon">
    <ul class="nav-second-level">
      @can('coupon-list')
      <li>
        <a href="{{ route('admin.coupons.index') }}">
          <i data-feather="list"></i> All Coupons
        </a>
      </li>
      @endcan

      @can('coupon-create')
      <li>
        <a href="{{ route('admin.coupons.create') }}">
          <i data-feather="plus-circle"></i> Add New
        </a>
      </li>
      @endcan
    </ul>
  </div>
</li>
@endcanany

{{-- Landing Page --}}
@canany(['campaign-list', 'campaign-create'])
@php $act = $on('campaign.*'); @endphp
<li @class(['menuitem-active' => $act])>
  <a href="#sidebar-landing-page" data-bs-toggle="collapse">
    <i data-feather="airplay"></i>
    <span> Landing Page </span>
    <span class="menu-arrow"></span>
  </a>
  <div @class(['collapse', 'show' => $act]) id="sidebar-landing-page">
    <ul class="nav-second-level">
      @can('campaign-list')
      <li><a href="{{ route('campaign.index') }}"><i data-feather="file-plus"></i> Campaign</a></li>
      @endcan
      @can('campaign-create')
      <li><a href="{{ route('campaign.create') }}"><i data-feather="file-plus"></i> Create</a></li>
      @endcan
    </ul>
  </div>
</li>
@endcanany

{{-- Send Custom SMS --}}
@can('sms-send')
<li @class(['menuitem-active' => $on('admin.sms.*')])>
  <a href="{{ route('admin.sms.custom.page') }}">
    <i data-feather="send"></i>
    <span>Send Custom SMS</span>
  </a>
</li>
@endcan

{{-- Facebook Page Post --}}
@canany(['pixel-manage'])
<li @class(['menuitem-active' => $on('admin.facebook_page.*')])>
  <a href="{{ route('admin.facebook_page.settings') }}">
    <i data-feather="share-2"></i>
    <span> Facebook Page Post </span>
  </a>
</li>
@endcanany

{{-- Live Ads Result --}}
@canany(['pixel-manage'])
@php $act = $on('admin.ads_analytics.*'); @endphp
<li @class(['menuitem-active' => $act])>
  <a href="#sidebar-ads-analytics" data-bs-toggle="collapse">
    <i data-feather="trending-up"></i>
    <span> Live Ads Result </span>
    <span class="menu-arrow"></span>
  </a>
  <div @class(['collapse', 'show' => $act]) id="sidebar-ads-analytics">
    <ul class="nav-second-level">
      <li><a href="{{ route('admin.ads_analytics.dashboard') }}"><i data-feather="layout"></i> Overview</a></li>
      <li><a href="{{ route('admin.ads_analytics.facebook') }}"><i data-feather="facebook"></i> Facebook Ads</a></li>
      <li><a href="{{ route('admin.ads_analytics.google') }}"><i data-feather="globe"></i> Google Ads</a></li>
      <li><a href="{{ route('admin.ads_analytics.tiktok') }}"><i data-feather="video"></i> TikTok Ads</a></li>
    </ul>
  </div>
</li>
@endcanany

{{-- G. Pixel and GTM --}}
@canany(['pixel-manage'])
@php $act = $on('tagmanagers.*', 'pixels.*', 'tiktok.pixels.*'); @endphp
<li @class(['menuitem-active' => $act])>
  <a href="#sidebar-pixel-gtm" data-bs-toggle="collapse">
    <i data-feather="save"></i>
    <span> G. Pixel and GTM </span>
    <span class="menu-arrow"></span>
  </a>
  <div @class(['collapse', 'show' => $act]) id="sidebar-pixel-gtm">
    <ul class="nav-second-level">
      <li><a href="{{ route('tagmanagers.index') }}"><i data-feather="file-plus"></i> Tag Manager</a></li>
      <li><a href="{{ route('pixels.index') }}"><i data-feather="file-plus"></i> Pixel Manage</a></li>
      <li><a href="{{ route('tiktok.pixels.index') }}"><i data-feather="film"></i> TikTok Pixel</a></li>
    </ul>
  </div>
</li>
@endcanany

</x-admin.menu-group>

{{-- ============================== WEBSITE CONTENT ============================== --}}
<x-admin.menu-group group="content" label="Website Content">

{{-- Blog --}}
@canany(['blog-list','blog-create','blog-edit','blog-delete'])
@php
    // rescue(): never let the menu badge break the admin panel (e.g. before the migration runs).
    $blogPendingComments = rescue(fn () => \App\Models\BlogComment::where('status', 'pending')->where('is_admin', false)->count(), 0, false);
    $act = $on('admin.blog.*', 'admin.blog_comments.*');
@endphp
<li @class(['menuitem-active' => $act])>
    <a href="#sidebar-blog" data-bs-toggle="collapse">
        <i data-feather="edit"></i>
        <span> Blog </span>
        @if($blogPendingComments > 0)
          <span class="badge bg-danger rounded-pill float-end me-2">{{ $blogPendingComments }}</span>
        @endif
        <span class="menu-arrow"></span>
    </a>

    <div @class(['collapse', 'show' => $act]) id="sidebar-blog">
        <ul class="nav-second-level">
            @can('blog-list')
            <li>
                <a href="{{ route('admin.blog.index') }}">
                    <i data-feather="list"></i>
                    All Blogs
                </a>
            </li>
            @endcan

            @can('blog-create')
            <li>
                <a href="{{ route('admin.blog.create') }}">
                    <i data-feather="plus-circle"></i>
                    Add New Blog
                </a>
            </li>
            @endcan

            @can('blog-list')
            <li>
                <a href="{{ route('admin.blog_comments.index') }}">
                    <i data-feather="message-square"></i>
                    Blog Comments
                    @if($blogPendingComments > 0)
                      <span class="badge bg-danger rounded-pill float-end">{{ $blogPendingComments }}</span>
                    @endif
                </a>
            </li>
            @endcan
        </ul>
    </div>
</li>
@endcanany

{{-- Testimonials --}}
@php $act = $on('admin.testimonial.*'); @endphp
<li @class(['menuitem-active' => $act])>
    <a href="#sidebar-testimonial" data-bs-toggle="collapse">
        <i data-feather="star"></i>
        <span> Testimonials </span>
        <span class="menu-arrow"></span>
    </a>
    <div @class(['collapse', 'show' => $act]) id="sidebar-testimonial">
        <ul class="nav-second-level">
            <li>
                <a href="{{ route('admin.testimonial.index') }}">
                    <i data-feather="list"></i>
                    All Testimonials
                </a>
            </li>
            <li>
                <a href="{{ route('admin.testimonial.create') }}">
                    <i data-feather="plus-circle"></i>
                    Add New
                </a>
            </li>
        </ul>
    </div>
</li>

{{-- Banner & Sliders --}}
@canany(['banner-list'])
<li @class(['menuitem-active' => $on('banners.*', 'banner_category.*')])>
    <a href="{{ route('banners.index') }}">
      <i data-feather="image"></i>
        <span> Banner & Sliders </span>
    </a>
</li>
@endcanany

{{-- Popup Offer --}}
@canany(['popup-list','popup-manage'])
<li @class(['menuitem-active' => $on('admin.popup.*')])>
    <a href="{{ route('admin.popup.index') }}">
        <i data-feather="message-square"></i>
        <span> Popup Offer </span>
    </a>
</li>
@endcanany

</x-admin.menu-group>

{{-- ============================== FINANCE ============================== --}}
<x-admin.menu-group group="finance" label="Finance">

{{-- Fund / Account --}}
@canany(['fund-list', 'fund-create', 'fund-edit'])
<li @class(['menuitem-active' => $on('admin.fund.*')])>
  <a href="{{ route('admin.fund.index') }}">
    <i data-feather="briefcase"></i>
    <span> Fund / Account</span>
  </a>
</li>
@endcanany

{{-- Expenses --}}
@canany(['expense-list', 'expense-create', 'expense-edit'])
<li @class(['menuitem-active' => $on('admin.expenses.*')])>
  <a href="{{ route('admin.expenses.index') }}">
    <i data-feather="credit-card"></i>
    <span>Expenses</span>
  </a>
</li>
@endcanany

{{-- Reports --}}
@canany(['report-view','order-report','purchase-report','expense-report','stock-report','profit-loss-report'])
@php $act = $on('admin.reports.*'); @endphp
<li @class(['menuitem-active' => $act])>
  <a href="#sidebar-report" data-bs-toggle="collapse">
    <i data-feather="pie-chart"></i>
    <span> Reports </span>
    <span class="menu-arrow"></span>
  </a>
  <div @class(['collapse', 'show' => $act]) id="sidebar-report">
    <ul class="nav-second-level">
      @canany(['order-report','report-view'])
      <li>
        <a href="{{ route('admin.reports.orders') }}">
          <i data-feather="file-text"></i> Order Report
        </a>
      </li>
      @endcanany

      @canany(['purchase-report','report-view'])
      <li>
        <a href="{{ route('admin.reports.purchases') }}">
          <i data-feather="shopping-bag"></i> Purchase Report
        </a>
      </li>
      @endcanany

      @canany(['expense-report','report-view'])
      <li>
        <a href="{{ route('admin.reports.expenses') }}">
          <i data-feather="trending-down"></i> Expense Report
        </a>
      </li>
      @endcanany

      @canany(['stock-report','report-view'])
      <li>
        <a href="{{ route('admin.reports.stock') }}">
          <i data-feather="archive"></i> Stock Report
        </a>
      </li>
      @endcanany

      @canany(['profit-loss-report','report-view'])
      <li>
        <a href="{{ route('admin.reports.profit_loss') }}">
          <i data-feather="activity"></i> Profit & Loss
        </a>
      </li>
      @endcanany

      @can('report-view')
      <li>
        <a href="{{ route('admin.reports.search_misses') }}">
          <i data-feather="search"></i> Search Misses
        </a>
      </li>
      @endcan
    </ul>
  </div>
</li>
@endcanany

</x-admin.menu-group>

{{-- ============================== TEAM & ACCESS ============================== --}}
<x-admin.menu-group group="team" label="Team & Access">

{{-- Users (Customers moved to the Customers section) --}}
@canany(['user-list', 'role-list', 'permission-list'])
@php $act = $on('users.*', 'roles.*', 'permissions.*'); @endphp
<li @class(['menuitem-active' => $act])>
  <a href="#sidebar-users" data-bs-toggle="collapse">
    <i data-feather="user"></i>
    <span> Users </span>
    <span class="menu-arrow"></span>
  </a>
  <div @class(['collapse', 'show' => $act]) id="sidebar-users">
    <ul class="nav-second-level">
      @can('user-list')
      <li><a href="{{ route('users.index') }}"><i data-feather="file-plus"></i> User</a></li>
      @endcan
      @can('role-list')
      <li><a href="{{ route('roles.index') }}"><i data-feather="file-plus"></i> Roles</a></li>
      @endcan
      @can('permission-list')
      <li><a href="{{ route('permissions.index') }}"><i data-feather="file-plus"></i> Permissions</a></li>
      @endcan
    </ul>
  </div>
</li>
@endcanany

{{-- CRM / HR --}}
@canany(['employee-list', 'attendance-list', 'leave-list', 'salary-list', 'bonus-list', 'salary-payment-list'])
@php $act = $on('admin.employees.*', 'admin.attendances.*', 'admin.leaves.*', 'admin.salaries.*', 'admin.bonuses.*', 'admin.salary_payments.*'); @endphp
<li @class(['menuitem-active' => $act])>
  <a href="#sidebar-crm" data-bs-toggle="collapse">
    <i data-feather="users"></i>
    <span> CRM / HR </span>
    <span class="menu-arrow"></span>
  </a>
  <div @class(['collapse', 'show' => $act]) id="sidebar-crm">
    <ul class="nav-second-level">
      @can('employee-list')
      <li><a href="{{ route('admin.employees.index') }}"><i data-feather="user"></i> Employees</a></li>
      @endcan
      @can('attendance-list')
      <li><a href="{{ route('admin.attendances.index') }}"><i data-feather="check-circle"></i> Attendance</a></li>
      @endcan
      @can('leave-list')
      <li><a href="{{ route('admin.leaves.index') }}"><i data-feather="calendar"></i> Leaves</a></li>
      @endcan
      @can('salary-list')
      <li><a href="{{ route('admin.salaries.index') }}"><i data-feather="dollar-sign"></i> Salaries</a></li>
      @endcan
      @can('bonus-list')
      <li><a href="{{ route('admin.bonuses.index') }}"><i data-feather="gift"></i> Bonuses</a></li>
      @endcan
      @can('salary-payment-list')
      <li><a href="{{ route('admin.salary_payments.index') }}"><i data-feather="credit-card"></i> Salary Payments</a></li>
      @endcan
    </ul>
  </div>
</li>
@endcanany

</x-admin.menu-group>

{{-- ============================== SETTINGS (collapsed by default) ============================== --}}
<x-admin.menu-group group="settings" label="Settings" :collapsed="true">

{{-- Site Setting --}}
@canany(['setting-list', 'social-list', 'contact-list'])
@php $act = $on('settings.*', 'socialmedias.*', 'contact.*', 'pages.*'); @endphp
<li @class(['menuitem-active' => $act])>
  <a href="#siebar-sitesetting" data-bs-toggle="collapse">
    <i data-feather="settings"></i>
    <span> Site Setting </span>
    <span class="menu-arrow"></span>
  </a>
  <div @class(['collapse', 'show' => $act]) id="siebar-sitesetting">
    <ul class="nav-second-level">
      @can('setting-list')
      <li><a href="{{ route('settings.index') }}"><i data-feather="file-plus"></i> General Setting</a></li>
      @endcan
      @can('social-list')
      <li><a href="{{ route('socialmedias.index') }}"><i data-feather="file-plus"></i> Social Media</a></li>
      @endcan
      @can('contact-list')
      <li><a href="{{ route('contact.index') }}"><i data-feather="file-plus"></i> Contact</a></li>
      @endcan
      @canany(['page-list', 'page-create', 'page-edit'])
      <li><a href="{{ route('pages.index') }}"><i data-feather="file-plus"></i> Create Page</a></li>
      @endcanany
    </ul>
  </div>
</li>
@endcanany

{{-- Email Settings --}}
@can('email-setting-list')
<li @class(['menuitem-active' => $on('email_setting', 'email_setting.*')])>
  <a href="{{ route('email_setting') }}">
    <i data-feather="mail"></i>
    <span>Email Settings</span>
  </a>
</li>
@endcan

{{-- API Integration --}}
@canany(['api-manage'])
@php $act = $on('paymentgeteway.*', 'manual-payment-gateway.*', 'smsgeteway.*', 'courierapi.*', 'admin.facebook_capi.*'); @endphp
<li @class(['menuitem-active' => $act])>
  <a href="#sidebar-api-integration" data-bs-toggle="collapse">
    <i data-feather="save"></i>
    <span> API Integration </span>
    <span class="menu-arrow"></span>
  </a>
  <div @class(['collapse', 'show' => $act]) id="sidebar-api-integration">
    <ul class="nav-second-level">
      <li><a href="{{ route('paymentgeteway.manage') }}"><i data-feather="file-plus"></i> Payment Gateway</a></li>
      <li><a href="{{ route('manual-payment-gateway.manage') }}"><i data-feather="credit-card"></i> Manual Payment</a></li>
      <li><a href="{{ route('smsgeteway.manage') }}"><i data-feather="file-plus"></i> SMS Gateway</a></li>
      <li><a href="{{ route('courierapi.manage') }}"><i data-feather="file-plus"></i> Courier API</a></li>
      <li><a href="{{ route('admin.facebook_capi.edit') }}"><i data-feather="facebook"></i> Facebook CAPI</a></li>
    </ul>
  </div>
</li>
@endcanany

{{-- Cron Job --}}
@canany(['api-manage'])
<li @class(['menuitem-active' => $on('admin.cron.*')])>
  <a href="{{ route('admin.cron.index') }}">
    <i data-feather="clock"></i>
    <span> Cron Job </span>
  </a>
</li>
@endcanany

{{-- SEO Settings --}}
@can('seo-manage')
<li @class(['menuitem-active' => $on('admin.seo_settings.*')])>
  <a href="{{ route('admin.seo_settings.index') }}">
    <i data-feather="globe"></i>
    <span>SEO Settings</span>
  </a>
</li>
@endcan

{{-- Sitemap Settings --}}
@can('sitemap-manage')
<li @class(['menuitem-active' => $on('admin.sitemap.*')])>
    <a href="{{ route('admin.sitemap.index') }}">
        <i data-feather="map"></i>
        <span> Sitemap Settings </span>
    </a>
</li>
@endcan

{{-- Clear Cache --}}
@can('cache-clear')
<li>
  <a href="{{ route('admin.clear.cache') }}"
     onclick="return confirm('Are you sure you want to clear all cache?')">
    <i data-feather="refresh-cw"></i>
    <span>Clear Cache</span>
  </a>
</li>
@endcan

{{-- Error Log --}}
@can('error-log-view')
<li @class(['menuitem-active' => $on('error-log.*')])>
  <a href="{{ route('error-log.index') }}">
    <i data-feather="file-text"></i>
    <span>Error Log</span>
  </a>
</li>
@endcan

</x-admin.menu-group>
  </ul>

  <p class="bg-menu-empty" hidden>No menu items match.</p>
</div>
