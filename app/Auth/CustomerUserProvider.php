<?php

namespace App\Auth;

use App\Models\Customer;
use Illuminate\Auth\EloquentUserProvider;

/**
 * The customer guard's user provider. Manual customers (added by an admin) are records only:
 * this provider never returns them, so they cannot sign in, be remembered, have a session
 * restored, or receive a password-reset link — whichever login path is used.
 */
class CustomerUserProvider extends EloquentUserProvider
{
    protected function newModelQuery($model = null)
    {
        return parent::newModelQuery($model)
            ->where('source', '!=', Customer::SOURCE_MANUAL);
    }
}
