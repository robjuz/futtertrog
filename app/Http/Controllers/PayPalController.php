<?php

namespace App\Http\Controllers;

use App\Deposit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Srmklive\PayPal\Services\ExpressCheckout;

class PayPalController extends Controller
{
    /**
     * @var ExpressCheckout
     */
    protected $paypal;

    /**
     * PayPalController constructor.
     *
     * @param \Srmklive\PayPal\Services\ExpressCheckout $paypal
     */
    public function __construct(ExpressCheckout $paypal)
    {
        $this->paypal = $paypal;
    }

    public function expressCheckout(Request $request)
    {
        $request->validate(['value' => 'required|numeric|min:0.01']);

        try {
            return DB::transaction(function () use ($request) {
                $request->user()->deposits()->create([
                    'value' => $request->input('value'),
                    'status' => Deposit::STATUS_PROCESSING,
                    'comment' => trans('Payed with PayPal'),
                ]);

                $response = $this->paypal->setExpressCheckout($request->user()->getCheckoutData());

                return redirect($response['paypal_link']);
            });
        } catch (\Throwable $e) {
            return back()->with('error', trans('Error processing PayPal payment'));
        }
    }

    public function expressCheckoutSuccess(Request $request)
    {
        $token = $request->input('token');
        // Verify Express Checkout Token
        $response = $this->paypal->getExpressCheckoutDetails($token);

        if (in_array(strtoupper($response['ACK']), ['SUCCESS', 'SUCCESSWITHWARNING'])) {

            // Perform transaction on PayPal

            $payment_status = $this->paypal->doExpressCheckoutPayment(
                $request->user()->getCheckoutData(),
                $token,
                $request->input('PayerID')
            );

            if (
                ! strcasecmp($payment_status['PAYMENTINFO_0_PAYMENTSTATUS'], 'Completed')
                || ! strcasecmp($payment_status['PAYMENTINFO_0_PAYMENTSTATUS'], 'Processed')
            ) {
                $request->user()
                    ->deposits()
                    ->whereStatus(Deposit::STATUS_PROCESSING)
                    ->update(['status' => Deposit::STATUS_OK]);

                return redirect('/')->with('success', trans('Success'));
            }
        }

        return redirect('/')->with('error', trans('Error processing PayPal payment'));
    }
}
