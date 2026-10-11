<?php

namespace App\Providers;

use App\Auth\CustomerUserProvider;
use App\Models\User;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        // Customer logins skip manual (admin-added) customers — see CustomerUserProvider.
        Auth::provider('customer-eloquent', function ($app, array $config) {
            return new CustomerUserProvider($app['hash'], $config['model']);
        });

        Gate::before(function ($user): ?bool {
            if (! $user instanceof User) {
                return null;
            }

            $isAdmin = $user->getKey() === 1
                || $user->roles->contains(
                    fn ($role) => strcasecmp($role->name, 'admin') === 0
                );

            return $isAdmin ? true : null;
        });
    }
}
