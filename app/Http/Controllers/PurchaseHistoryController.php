<?php

namespace App\Http\Controllers;

use App\Models\PaymentOrder;
use Illuminate\View\View;

class PurchaseHistoryController extends Controller
{
    public function index(): View
    {
        $orders = PaymentOrder::query()
            ->where('user_id', auth()->id())
            ->with('shopProduct')
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('purchases.index', [
            'orders' => $orders,
        ]);
    }
}
