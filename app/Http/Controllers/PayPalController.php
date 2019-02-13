<?php

namespace App\Http\Controllers;

use App\Deposit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Srmklive\PayPal\Services\ExpressCheckout;

class PayPalController extends Controller
{

    /**
     * @var ExpressCheckout
     */
    protected $provider;

    /**
     * PayPalController constructor.
     *
     * @throws \Exception
     */
    public function __construct()
    {
        $this->provider = new ExpressCheckout();
    }


    public function expressCheckout(Request $request)
    {
        $request->validate(['value' => 'required|numeric|min:0.01']);
        try {
            return DB::transaction(function () use ($request) {
                $request->user()->deposits()->create([
                    'value' => $request->input('value'),
                    'status' => Deposit::STATUS_PROCESSING,
                    'comment' => trans('Payed with PayPal')
                ]);

                $response = $this->provider->setExpressCheckout($this->getCheckoutData($request->input('value')));

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
        $response = $this->provider->getExpressCheckoutDetails($token);

        if (in_array(strtoupper($response['ACK']), ['SUCCESS', 'SUCCESSWITHWARNING'])) {

            // Perform transaction on PayPal
            $deposit = $request->user()->deposits()->whereStatus(Deposit::STATUS_PROCESSING)->latest()->first();

            $payment_status = $this->provider->doExpressCheckoutPayment(
                $this->getCheckoutData($deposit->value),
                $token,
                $request->input('PayerID')
            );

            if (
                !strcasecmp($payment_status['PAYMENTINFO_0_PAYMENTSTATUS'], 'Completed')
                || !strcasecmp($payment_status['PAYMENTINFO_0_PAYMENTSTATUS'], 'Processed')
            ) {
                $deposit->update(['status' => Deposit::STATUS_OK]);
                return redirect('/')->with('success', trans('Success'));
            }
        }

        return redirect('/')->with('error', trans('Error processing PayPal payment'));
    }

    /**
     * @param \Illuminate\Http\Request $request
     *
     * @return array
     */
    public function getCheckoutData($value): array
    {
        return [
            'items' => [
                [
                    'name' => trans('Futtertrog deposit'),
                    'price' => $value,
                    'qty' => 1
                ]
            ],
            'invoice_description' => null,
            'invoice_id' => null,
            'return_url' => route('paypal.express_checkout_success'),
            'cancel_url' => url('/'),
            'total' => $value
        ];
    }
}
