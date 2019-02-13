<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PayPalDepositTest extends TestCase
{
    /** @test */
    public function it_allows_the_user_to_make_a_deposit_with_paypal()
    {
        $this->login();

        $this->post(route('paypal.express_checkout'), ['value' => 10]);

        $this->assertEquals(10, auth()->user()->balance);
    }
}
