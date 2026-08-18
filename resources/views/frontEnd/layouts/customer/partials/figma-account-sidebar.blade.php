@php
    $accountCustomer = $customer ?? Auth::guard('customer')->user();
    $accountCustomerId = $accountCustomer->id;
    $accountPendingOrders = $pendingOrdersCount ?? \App\Models\Order::where('customer_id', $accountCustomerId)
        ->whereNotIn('order_status', ['6', '11'])
        ->count();
    $accountProfileImage = $profileImage ?? ($accountCustomer->image ? asset($accountCustomer->image) : null);
    $accountInitial = $customerInitial ?? strtoupper(substr($accountCustomer->name ?? 'U', 0, 1));
    $accountUsername = $accountCustomer->phone ?: $accountCustomer->email;
@endphp

<aside class="bilai-account-sidebar">
    <div class="bilai-account-sidebar-card">
      <section class="bilai-account-profile" aria-label="Customer profile">
        <div class="bilai-account-profile-head">
            @if($accountProfileImage)
                <img src="{{ $accountProfileImage }}"
                     onerror="this.style.display='none';this.nextElementSibling.style.display='flex';"
                     class="bilai-account-avatar"
                     alt="{{ $accountCustomer->name }}">
                <div class="bilai-account-avatar-placeholder" style="display:none;">{{ $accountInitial }}</div>
            @else
                <div class="bilai-account-avatar-placeholder">{{ $accountInitial }}</div>
            @endif

            <div class="bilai-account-profile-copy">
                <h2 class="bilai-account-profile-name">{{ $accountCustomer->name ?? 'Customer' }}</h2>
                <p class="bilai-account-profile-username">{{ $accountUsername }}</p>
            </div>
        </div>

      </section>

      <nav class="bilai-account-menu" aria-label="Customer account">
        <div class="bilai-account-menu-list">
            <a href="{{ route('customer.account') }}" class="bilai-account-menu-link {{ request()->routeIs('customer.account') ? 'active' : '' }}">
                <span class="bilai-account-menu-icon"><i class="fa fa-th-large"></i></span>
                Dashboard
            </a>
            <a href="{{ route('customer.orders') }}" class="bilai-account-menu-link {{ request()->routeIs('customer.orders') ? 'active' : '' }}">
                <span class="bilai-account-menu-icon"><i class="fa fa-list-alt"></i></span>
                Orders
                @if($accountPendingOrders > 0)
                    <span class="bilai-account-menu-badge">{{ $accountPendingOrders }}</span>
                @endif
            </a>
            <a href="{{ route('customer.profile_edit') }}" class="bilai-account-menu-link {{ request()->routeIs('customer.profile_edit') ? 'active' : '' }}">
                <span class="bilai-account-menu-icon"><i class="fa fa-user-o"></i></span>
                Profile
            </a>
            <a href="{{ route('customer.wishlist') }}" class="bilai-account-menu-link {{ request()->routeIs('customer.wishlist') ? 'active' : '' }}">
                <span class="bilai-account-menu-icon"><i class="fa fa-heart-o"></i></span>
                Wishlist
            </a>
            <a href="{{ route('customer.addresses') }}" class="bilai-account-menu-link {{ request()->routeIs('customer.addresses') ? 'active' : '' }}">
                <span class="bilai-account-menu-icon"><i class="fa fa-map-marker"></i></span>
                Addresses
            </a>
            <a href="{{ route('customer.rewards') }}" class="bilai-account-menu-link {{ request()->routeIs('customer.rewards') ? 'active' : '' }}">
                <span class="bilai-account-menu-icon"><i class="fa fa-star-o"></i></span>
                Reward Points
            </a>
            <a href="{{ route('customer.order_track') }}" class="bilai-account-menu-link {{ request()->routeIs('customer.order_track') ? 'active' : '' }}">
                <span class="bilai-account-menu-icon"><i class="fa fa-truck"></i></span>
                Track Order
            </a>
            <a href="{{ route('customer.refunds') }}" class="bilai-account-menu-link {{ request()->routeIs('customer.refunds*') ? 'active' : '' }}">
                <span class="bilai-account-menu-icon"><i class="fa fa-undo"></i></span>
                Return Request
            </a>
            <a href="{{ route('complaint') }}" class="bilai-account-menu-link {{ request()->routeIs('complaint') ? 'active' : '' }}">
                <span class="bilai-account-menu-icon"><i class="fa fa-headphones"></i></span>
                Support Ticket
            </a>
        </div>

        <div class="bilai-account-logout">
            <a href="{{ route('customer.logout') }}"
               onclick="event.preventDefault(); document.getElementById('figma-account-logout-form').submit();"
               class="bilai-account-menu-link">
                <span class="bilai-account-menu-icon"><i class="fa fa-sign-out"></i></span>
                Logout
            </a>
            <form id="figma-account-logout-form" action="{{ route('customer.logout') }}" method="POST" style="display:none;">
                @csrf
            </form>
        </div>
      </nav>
    </div>
</aside>
