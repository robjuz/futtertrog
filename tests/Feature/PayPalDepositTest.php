<?php

namespace Tests\Feature;

use Srmklive\PayPal\Services\ExpressCheckout;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

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
    public function it_creates_a_deposite_when_payment_is_successfull()
    {
        $provider = new ExpressCheckout();
    }
}
