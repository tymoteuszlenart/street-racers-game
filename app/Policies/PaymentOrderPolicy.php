<?php

namespace App\Policies;

use App\Models\PaymentOrder;
use App\Models\User;

class PaymentOrderPolicy
{
    public function view(User $user, PaymentOrder $paymentOrder): bool
    {
        return $paymentOrder->user_id === $user->id || $user->is_admin;
    }
}
