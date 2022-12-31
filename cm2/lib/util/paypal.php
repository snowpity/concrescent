<?php

require_once dirname(__FILE__).'/../../config/config.php';

class cm_paypal {

	private $event_name;
	private $api_url;
	private $client_id;
	private $secret;
	private $currency;
	private $token;

	public function __construct($token = null) {
		$config = $GLOBALS['cm_config']['event'];
		$this->event_name = $config['name'];
		$config = $GLOBALS['cm_config']['paypal'];
		$this->api_url = $config['api_url'];
		$this->client_id = $config['client_id'];
		$this->secret = $config['secret'];
		$this->currency = $config['currency'];
		$this->token = $token;
	}

	public function get_token() {
		if ($this->token) {
			//Check expiration
			if($this->token['expires'] > time())
			return $this->token;
		}
		$curl = curl_init('https://' . $this->api_url . '/v1/oauth2/token');
		curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_HTTPHEADER, array(
			'Accept: application/json',
			'Accept-Language: en_US'
		));
		curl_setopt($curl, CURLOPT_USERPWD, (
			$this->client_id . ':' . $this->secret
		));
		curl_setopt($curl, CURLOPT_POSTFIELDS, (
			'grant_type=client_credentials'
		));
		$result = curl_exec($curl);
		curl_close($curl);
		$this->token = json_decode($result, true);
		//Setup the expiration info
		$this->token['expires'] = $this->token['expires_in'] + time();
		return $this->token;
	}

	public function api($method, $data = null) {
		$curl = curl_init('https://' . $this->api_url . '/v1/' . $method);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_HTTPHEADER, array(
			'Content-Type: application/json',
			'Authorization: ' . $this->token['token_type']
			            . ' ' . $this->token['access_token']
		));
		if(!is_null($data)){
			curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
			curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
		}
		$result = curl_exec($curl);
		$http_status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		curl_close($curl);
		if($http_status == 401)
		{
			//We got de-authed, try to get a new token
			$this->token['expires'] = time()-1;
			if($this->get_token()['expires'] > time())
			{
				//Retry the request
				return $this->api($method,$data);
			}
		}
		else if($http_status >= 400 && $http_status < 502 )
		{
			//Spit the error to the lgo
			error_log('Error ' . $http_status . ' communicating with paypal: Data received:\n' . $result);
			error_log('Submitted data:\n' . print_r($data,true));
		}
		return json_decode($result, true);
	}

	public function create_item($name, $price) {
		return array(
			'quantity' => '1',
			'name' => $name,
			'price' => number_format($price, 2, '.', ''),
			'currency' => $this->currency
		);
	}

	public function create_total($total) {
		return array(
			'total' => number_format($total, 2, '.', ''),
			'currency' => $this->currency
		);
	}

	public function create_transaction($items, $total, $invoice_number) {
		return array(
			'amount' => $total,
			'description' => $this->event_name,
			'invoice_number' => $invoice_number,
			'item_list' => array('items' => $items)
		);
	}

	public function create_cc($vendor, $number, $cvv2, $exp_month, $exp_year, $first_name, $last_name) {
		return array(
			'number' => preg_replace('/[^0-9]+/', '', $number),
			'type' => strtolower($vendor),
			'expire_month' => (int)$exp_month,
			'expire_year' => (((int)$exp_year < 100) ? ((int)$exp_year + 2000) : (int)$exp_year),
			'cvv2' => $cvv2,
			'first_name' => $first_name,
			'last_name' => $last_name
		);
	}

	public function create_payment_pp($return_url, $cancel_url, $transaction) {
		return $this->api('payments/payment', array(
			'intent' => 'sale',
			'redirect_urls' => array(
				'return_url' => $return_url,
				'cancel_url' => $cancel_url
			),
			'payer' => array(
				'payment_method' => 'paypal'
			),
			'transactions' => array($transaction)
		));
	}

	public function create_payment_cc($return_url, $cancel_url, $transaction, $cc) {
		return $this->api('payments/payment', array(
			'intent' => 'sale',
			'redirect_urls' => array(
				'return_url' => $return_url,
				'cancel_url' => $cancel_url
			),
			'payer' => array(
				'payment_method' => 'credit_card',
				'funding_instruments' => array(
					array('credit_card' => $cc)
				)
			),
			'transactions' => array($transaction)
		));
	}

	public function get_payment_link($payment, $rel) {
		if ($payment && isset($payment['links'])) {
			foreach ($payment['links'] as $link) {
				if ($link && isset($link['rel'])) {
					if ($link['rel'] == $rel) {
						return $link['href'];
					}
				}
			}
		}
		return null;
	}

	public function get_payment_approval_url($payment) {
		return $this->get_payment_link($payment, 'approval_url');
	}

	public function execute_payment($payment_id, $payer_id) {
		return $this->api(
			'payments/payment/' . $payment_id . '/execute',
			array('payer_id' => $payer_id)
		);
	}

	public function execute_refund($sale_id,$invoice_number, $amount, $note)
	{
		return $this->api(
			'payments/sale/' . $sale_id . '/refund',
			array('amount' => array( 'total' => $amount,'currency' => $this->currency ),
				'invoice_number' => $invoice_number,
				'description' => $note
			)
		);
	}

	public function retrieve_payment($payment_id)
	{
		return $this->api(
			'payments/payment/' . $payment_id
		);
	}

	public function get_transaction_id($sale) {
		if (!$sale) return null;
		if (!isset($sale['transactions'])) return null;
		if (!isset($sale['transactions'][0])) return null;
		if (!isset($sale['transactions'][0]['related_resources'])) return null;
		if (!isset($sale['transactions'][0]['related_resources'][0])) return null;
		if (!isset($sale['transactions'][0]['related_resources'][0]['sale'])) return null;
		if (!isset($sale['transactions'][0]['related_resources'][0]['sale']['id'])) return null;
		return $sale['transactions'][0]['related_resources'][0]['sale']['id'];
	}

}
