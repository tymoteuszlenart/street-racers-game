<?php

namespace App\Http\Controllers;

use App\Models\PaymentOrder;
use Illuminate\View\View;

class AdminPurchaseController extends Controller
{
    public function index(): View
    {
        $orders = PaymentOrder::query()
            ->with(['user', 'shopProduct'])
            ->orderByDesc('created_at')
            ->paginate(30);

        return view('admin.purchases.index', [
            'orders' => $orders,
        ]);
    }

    public function show(PaymentOrder $paymentOrder): View
    {
        $this->authorize('view', $paymentOrder);

        $paymentOrder->load(['user', 'shopProduct']);

        return view('admin.purchases.show', [
            'order' => $paymentOrder,
        ]);
    }
}
