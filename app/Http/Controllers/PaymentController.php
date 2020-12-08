<?php

namespace App\Http\Controllers;

use Request;
use Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use PayPalCheckoutSdk\Core\PayPalHttpClient;
use PayPalCheckoutSdk\Core\SandboxEnvironment;
use PayPalCheckoutSdk\Core\LiveEnvironment;
use PayPalCheckoutSdk\Orders\OrdersCreateRequest;
use PayPalCheckoutSdk\Orders\OrdersGetRequest;
use PayPalCheckoutSdk\Orders\OrdersCaptureRequest;
use App\PaypalTransaction;
use App\Http\Controllers\BaseController as BaseController;

class PaymentController extends BaseController
{
    public function __construct()
    {
        $paypal_conf = \Config::get('paypal');
        $environment = $paypal_conf['mode'] === 'live' ?
            new LiveEnvironment($paypal_conf['client_id'], $paypal_conf['secret']) :
            new SandboxEnvironment($paypal_conf['client_id'], $paypal_conf['secret']);
        $this->client = new PayPalHttpClient($environment);
    }

    public function create(Request $request)
    {
        $input = $request::all();
        $validator = Validator::make($input, [
            'period' => [
                'required',
                Rule::in(
                    [
                        '1',
                        '3',
                        '6',
                        '12'
                    ]
                )
            ]
        ]);
        if ($validator->fails()) {
            return $this->sendError('bad_request', $validator->errors(), 400);
        }
        $prices = [
            '1' => 4.99,
            '3' => 13.99,
            '6' => 25.99,
            '12' => 49.99,
        ];
        $request = new OrdersCreateRequest();
        $request->prefer('return=representation');
        $url = env('SPA_URL') . '/account/';
        $request->body = [
            'intent' => 'CAPTURE',
            'application_context' =>
                [
                    'return_url' => $url,
                    'cancel_url' => $url
                ],
            'purchase_units' =>
                [
                    0 => [
                        'description' => 'Subscription for '.$input['period'].' month(s)',
                        'amount' => [
                            'currency_code' => 'EUR',
                            'value' => $prices[$input['period']],
                        ],
                    ]
                ]
        ];
        try {
            $response = $this->client->execute($request);
        } catch (Exception $e) {
            return $this->sendError('paypal_error', $e->getMessage(), 500);
        }
        $checkout_url = '';
        foreach ($response->result->links as $link) {
            if ($link->rel === 'approve') {
                $checkout_url = $link->href;
            }
        }
        PaypalTransaction::create([
            'user_id' => Auth::user()->id,
            'token' => $response->result->id,
            'url' => $checkout_url,
            'status' => $response->result->status,
            'period' => $input['period'],
            'ammount' => $prices[$input['period']]

        ]);
        return $this->sendResponse(['url' => $checkout_url], 200);
    }
    
    public function check(Request $request)
    {
        $input = $request::all();
        $validator = Validator::make($input, [
            'token' => 'required',
        ]);
        if ($validator->fails()) {
            return $this->sendError('bad_request', $validator->errors(), 400);
        }
        $request = new OrdersGetRequest($input['token']);
        try {
            $response = $this->client->execute($request);
        } catch (Exception $e) {
            return $this->sendError('paypal_error', $e->getMessage(), 500);
        }
        if($response->result->status === 'APPROVED'){
            $request = new OrdersCaptureRequest($input['token']);
            try {
                $response = $this->client->execute($request);
            } catch (Exception $e) {
                return $this->sendError('paypal_error', $e->getMessage(), 500);
            }    
            // TO DO: update user subscription if success
        }
        $transaction = PaypalTransaction::where('token', $input['token'])->first();
        $transaction->status = $response->result->status;
        $transaction->save();
        return $this->sendResponse(['status' => $response->result->status], 200);
    }
}
