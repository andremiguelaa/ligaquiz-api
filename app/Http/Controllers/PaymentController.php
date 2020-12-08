<?php

namespace App\Http\Controllers;

use Request;
use Validator;
use Illuminate\Validation\Rule;
use PayPal\Rest\ApiContext;
use PayPal\Auth\OAuthTokenCredential;
use PayPal\Api\Payer;
use PayPal\Api\Item;
use PayPal\Api\ItemList;
use PayPal\Api\Amount;
use PayPal\Api\Transaction;
use PayPal\Api\RedirectUrls;
use PayPal\Api\Payment;
use PayPal\Api\PaymentExecution;
use App\Http\Controllers\BaseController as BaseController;

class PaymentController extends BaseController
{
    public function __construct()
    {
        $paypal_conf = \Config::get('paypal');
        $this->_api_context = new ApiContext(
            new OAuthTokenCredential(
                $paypal_conf['client_id'],
                $paypal_conf['secret']
            )
        );
        $this->_api_context->setConfig($paypal_conf['settings']);
    }

    public function payWithpaypal(Request $request)
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
        $amountToBePaid = $prices[$input['period']];
        $payer = new Payer();
        $payer->setPaymentMethod('paypal');
        $item = new Item();
        $item->setName('Subscription for '.$input['period'].' month(s)')
              ->setCurrency('EUR')
              ->setQuantity(1)
              ->setPrice($amountToBePaid);
        $item_list = new ItemList();
        $item_list->setItems(array($item));
        $amount = new Amount();
        $amount->setCurrency('EUR')->setTotal($amountToBePaid);
        $redirect_urls = new RedirectUrls();
        $url = env('SPA_URL') . '/account/';
        $redirect_urls->setReturnUrl($url)->setCancelUrl($url);
        $transaction = new Transaction();
        $transaction->setAmount($amount)
              ->setItemList($item_list)
              ->setDescription('Your transaction description');
        $payment = new Payment();
        $payment->setIntent('Sale')
              ->setPayer($payer)
              ->setRedirectUrls($redirect_urls)
              ->setTransactions(array($transaction));
        try {
            $payment->create($this->_api_context);
        } catch (\PayPal\Exception\PPConnectionException $ex) {
            if (\Config::get('app.debug')) {
                return $this->sendError('connection_timeout', [], 504);
            } else {
                return $this->sendError('unknown_error', [], 500);
            }
        }
        foreach ($payment->getLinks() as $link) {
            if ($link->getRel() == 'approval_url') {
                $redirect_url = $link->getHref();
                break;
            }
        }
        if (isset($redirect_url)) {
            return $this->sendResponse([
                'redirect_url' => $redirect_url
            ], 200);
        }
        return $this->sendError('unknown_error', [], 500);
    }
    
    public function getPaymentStatus(Request $request)
    {
        $input = $request::all();
        $validator = Validator::make($input, [
            'paymentId' => 'required',
        ]);
        if ($validator->fails()) {
            return $this->sendError('bad_request', $validator->errors(), 400);
        }
        $payment_id = $input['paymentId'];
        if (empty($input['PayerID']) || empty($input['token'])) {
            return $this->sendError('payment_failed', [], 400);
        }
        $payment = Payment::get($payment_id, $this->_api_context);
        $execution = new PaymentExecution();
        $execution->setPayerId($input['PayerID']);
        $result = $payment->execute($execution, $this->_api_context);
        if ($result->getState() == 'approved') {
            // TODO: Update user subscription
            return $this->sendResponse(['message'=>'payment_success'], 200);
        }
        return $this->sendError('payment_failed', [], 400);
    }
}
