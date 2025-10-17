<?php

namespace App\Lib\Util;

use App\Config\Module\Event;
use App\Config\Module\Paypal;
use App\Payment\Paypal\ApiResult;
use App\Payment\Paypal\PaypalFailureException;
use App\Payment\Paypal\Token;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class cm_paypal {
	public function __construct(
        private readonly Paypal $paypal,
        private readonly Event  $event,
        private readonly CacheInterface $cache,
        private readonly LoggerInterface $paymentLogger,
    ) {
	}

    public Token $token {
        get {
            return $this->cache->get(
                Token::class,
                function (ItemInterface $item): Token {
                    $this->paymentLogger->debug('Regenerating paypal token...');
                    $curl = curl_init('https://' . $this->paypal->apiUrl . '/v1/oauth2/token');
                    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
                    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($curl, CURLOPT_HTTPHEADER, array(
                        'Accept: application/json',
                        'Accept-Language: en_US'
                    ));
                    curl_setopt($curl, CURLOPT_USERPWD, (
                        $this->paypal->clientId . ':' . $this->paypal->secret
                    ));
                    curl_setopt($curl, CURLOPT_POSTFIELDS, (
                    'grant_type=client_credentials'
                    ));

                    $result = curl_exec($curl);
                    $http_status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
                    curl_close($curl);

                    $tokenArray = json_decode($result, true, 512, JSON_THROW_ON_ERROR);

                    $tokenArray['expires'] = $tokenArray['expires_in'] + time();

                    if (isset($tokenArray['error'])) {
                        $this->paymentLogger->critical(
                            'Paypal critical error. Paypal said : {errorDescription} ({error})',
                            ['sub' => 'paypal', 'code' => $http_status,  'errorDescription' => $tokenArray['error_description'], 'error' => $tokenArray['error']]
                        );
                        throw new PaypalFailureException('Paypal critical error.');
                    }

                    $token = Token::fromArray($tokenArray);


                    $item->expiresAfter($token->expiresIn)->set($token);

                    $this->paymentLogger->debug('Regenerated a paypal token.');

                    return $token;
                },
            );
        }
	}

    public function pruneToken(): void
    {
        $this->cache->delete(Token::class);
    }

	private function api($method, $data = null) {
        $callApi = function ($method, $data)  use (&$failure) : ApiResult {
            $token = $this->token;
            $curl = curl_init('https://' . $this->paypal->apiUrl . '/v1/' . $method);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                "Authorization: $token->tokenType $token->accessToken"
            ]);
            if(!is_null($data)){
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
                curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data, JSON_THROW_ON_ERROR));
            }
            $result = curl_exec($curl);
            $http_status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            curl_close($curl);

            return new ApiResult($result, $http_status);
        };

        $result = $callApi($method,$data);

		if ($result->httpStatus === 401)
		{
			//We got de-authed, try to get a new token
			$this->pruneToken();

            $result = $callApi($method,$data);
		}

        if ($result->httpStatus >= 400 && $result->httpStatus < 502 )
        {
            $this->paymentLogger->critical(
                'Paypal critical error. Paypal response was : {payload}',
                ['sub' => 'paypal', 'code' => $result->httpStatus, 'payload' => $result->data]
            );
        }
        return json_decode($result->data, true, 512, JSON_THROW_ON_ERROR);
	}

	public function create_item($name, $price, $tax): array
    {
		return [
			'quantity' => '1',
			'name' => $name,
			'price' => number_format($price, 2, '.', ''),
			'currency' => $this->paypal->currency,
            'tax' => number_format($tax, 2, '.', ''),
        ];
	}

	public function create_total($total, $tax): array
    {
		return [
			'total' => number_format($total, 2, '.', ''),
			'currency' => $this->paypal->currency,
            'details' => [
                "subtotal" => number_format($total-$tax, 2, '.', ''),
                "tax" => number_format($tax, 2, '.', ''),
            ]
        ];
	}

	public function create_transaction($items, $total, $invoice_number): array
    {
		return [
			'amount' => $total,
			'description' => $this->event->name,
			'invoice_number' => $invoice_number,
			'item_list' => ['items' => $items]
        ];
	}

	public function create_payment_pp($return_url, $cancel_url, $transaction) {
		return $this->api('payments/payment', [
			'intent' => 'sale',
			'redirect_urls' => [
				'return_url' => $return_url,
				'cancel_url' => $cancel_url
            ],
			'payer' => [
				'payment_method' => 'paypal'
            ],
			'transactions' => [$transaction]
        ]);
	}

	public function get_payment_link($payment, string $rel) {
        foreach ($payment['links'] ?? [] as $link) {
            if (($link['rel'] ?? false) === $rel) {
                return $link['href'];
            }
        }
		return null;
	}

	public function get_payment_approval_url($payment) {
		return $this->get_payment_link($payment, 'approval_url');
	}

	public function execute_payment($payment_id, $payer_id) {
		return $this->api(
			"payments/payment/$payment_id/execute",
			['payer_id' => $payer_id]
		);
	}

	public function execute_refund($sale_id,$invoice_number, $amount, $note)
	{
		return $this->api(
			"payments/sale/$sale_id/refund",
			['amount' => ['total' => $amount,'currency' => $this->paypal->currency],
				'invoice_number' => $invoice_number,
				'description' => $note
            ]
		);
	}

	public function retrieve_payment($payment_id)
	{
		return $this->api("payments/payment/$payment_id");
	}

	public function get_transaction_id($sale) {
        return $sale['transactions'][0]['related_resources'][0]['sale']['id'] ?? null;
    }
}
