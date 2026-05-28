<?php

namespace Tests\Unit;

use App\Enums\PaymentOrderStatus;
use App\Models\PaymentOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentOrderFactoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_pending_factory_state(): void
    {
        $order = PaymentOrder::factory()->pending()->create();

        $this->assertSame(PaymentOrderStatus::Pending, $order->status);
        $this->assertNull($order->provider_event_id);
        $this->assertNull($order->granted_payload);
        $this->assertNull($order->fulfilled_at);
        $this->assertFalse($order->isFulfilled());
    }

    public function test_paid_factory_state(): void
    {
        $order = PaymentOrder::factory()->paid()->create();

        $this->assertSame(PaymentOrderStatus::Paid, $order->status);
        $this->assertNotNull($order->provider_event_id);
        $this->assertIsArray($order->granted_payload);
        $this->assertNotNull($order->fulfilled_at);
        $this->assertTrue($order->isFulfilled());
    }

    public function test_uuid_is_assigned_when_not_provided(): void
    {
        $order = PaymentOrder::factory()->create(['uuid' => null]);

        $this->assertNotNull($order->uuid);
        $this->assertNotSame('', $order->uuid);
    }
}
