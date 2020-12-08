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
use Carbon\Carbon;
use App\PaypalTransaction;
use App\User;
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
            '3' => 13.98,
            '6' => 25.98,
            '12' => 47.88,
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
        $transaction = PaypalTransaction::where('token', $input['token'])->first();
        $user = User::find(Auth::user()->id);
        $request = new OrdersGetRequest($input['token']);
        try {
            $response = $this->client->execute($request);
        } catch (Exception $e) {
            return $this->sendError('paypal_error', $e->getMessage(), 500);
        }
        if ($response->result->status === 'APPROVED') {
            $request = new OrdersCaptureRequest($input['token']);
            try {
                $response = $this->client->execute($request);
            } catch (Exception $e) {
                return $this->sendError('paypal_error', $e->getMessage(), 500);
            }
            if (in_array('regular_player', $user->getRoles())) {
                $startDate = new Carbon(
                    $user->roles['regular_player']
                );
                $startDate->midDay()->addDays(2);
            } else {
                $startDate = Carbon::tomorrow()->midDay();
                $fisrtMondayThisMonth = Carbon::tomorrow()->firstOfMonth(Carbon::MONDAY)->midDay();
                if ($startDate >= $fisrtMondayThisMonth) {
                    $startDate = Carbon::tomorrow()->midDay();
                    $startDate->day = 15;
                    $startDate->addMonth();
                }
                $startDate->firstOfMonth(Carbon::MONDAY)->midDay();
            }
            $month = $startDate->copy();
            $month->day = 15;
            $deadlines = [];
            for ($i=0; $i <= 12; $i++) {
                $firstMonday = $month->copy()->firstOfMonth(Carbon::MONDAY)->midDay();
                if ($firstMonday > $startDate) {
                    array_push($deadlines, $firstMonday->subDays(2)->format('Y-m-d'));
                }
                $month->addMonth();
            }
            $roles = $user->roles;
            $roles['regular_player'] = $deadlines[$transaction->period-1];
            $user->roles = $roles;
            $user->save();
        }
        $transaction->status = $response->result->status;
        $transaction->save();
        return $this->sendResponse([
            'status' => $response->result->status,
            'user' => $user
        ], 200);
    }
}
