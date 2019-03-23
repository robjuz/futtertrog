<?php

namespace Tests\Feature;

use App\Deposit;
use App\User;
use Mockery;
use Srmklive\PayPal\Services\ExpressCheckout;
use Tests\TestCase;

class PayPalDepositTest extends TestCase
{
    /** @test */
    public function it_redirects_to_paypal_when_requested()
    {
        $this->login();

        $this->post(route('paypal.express_checkout'), ['value' => 10])
            ->assertRedirect()
            ->assertSee('paypal.com');
    }

    /** @test */
    public function it_creates_a_deposit_when_payment_is_successful()
    {
        /** @var User $user */
        $user = factory(User::class)->create();
        $user->deposits()
            ->create(
                [
                    'value' => 10,
                    'status' => Deposit::STATUS_PROCESSING,
                ]
            );

        $token = "some_token";
        $PayerID = "123";

        $paypalMock = Mockery::mock(ExpressCheckout::class);
        $paypalMock->shouldReceive('getExpressCheckoutDetails')
            ->with($token)
            ->andReturn(['ACK' => 'SUCCESS'])
            ->once();

        $paypalMock->shouldReceive('doExpressCheckoutPayment')
            ->with($user->getCheckoutData(), $token, $PayerID)
            ->andReturn(['PAYMENTINFO_0_PAYMENTSTATUS' => 'Completed'])
            ->once();

        $this->app->instance(ExpressCheckout::class, $paypalMock);

        $this->assertEquals(0, $user->balance);
        $this->login($user)
            ->get(route('paypal.express_checkout_success', compact('token', 'PayerID')));

        $this->assertEquals(10, $user->balance);
    }
}
