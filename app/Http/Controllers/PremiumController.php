<?php

namespace App\Http\Controllers;

use App\Exceptions\CheckoutRateLimitedException;
use App\Models\ShopProduct;
use App\Services\PaymentCheckoutService;
use App\Services\PremiumFuelService;
use App\Services\ShopCatalogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

class PremiumController extends Controller
{
    public function __construct(
        private readonly ShopCatalogService $shopCatalogService,
        private readonly PaymentCheckoutService $paymentCheckoutService,
        private readonly PremiumFuelService $premiumFuelService,
    ) {}

    public function index(): View
    {
        $user = auth()->user();
        $profile = $user->playerProfile;
        $premiumAtCap = $profile !== null && ! $this->premiumFuelService->hasCapacity($profile);

        return view('premium.index', [
            'products' => $this->shopCatalogService->listActiveProducts(),
            'profile' => $profile,
            'premiumAtCap' => $premiumAtCap,
            'paidPremiumMax' => (int) config('game.shop.paid_premium_fuel_max', 20),
        ]);
    }

    public function checkout(ShopProduct $shopProduct): RedirectResponse
    {
        if (! $shopProduct->active) {
            abort(404);
        }

        try {
            $result = $this->paymentCheckoutService->createCheckoutSession(
                auth()->user(),
                $shopProduct,
            );
        } catch (CheckoutRateLimitedException $exception) {
            throw new TooManyRequestsHttpException(
                $exception->retryAfterSeconds,
                $exception->getMessage(),
                $exception,
            );
        } catch (ValidationException $exception) {
            return redirect()
                ->route('premium.index')
                ->withErrors($exception->errors());
        }

        return redirect()->away($result->checkoutUrl);
    }

    public function success(): View
    {
        return view('premium.success');
    }

    public function cancel(): View
    {
        return view('premium.cancel');
    }
}
