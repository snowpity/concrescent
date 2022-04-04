<?php

namespace CM3_Lib\Modules\Payment\paypal;

use GuzzleHttp\Exception\RequestException;

class PayProcessor implements \CM3_Lib\Modules\Payment\PayProcessorInterface
{
    private string $api_url = '';
    private array $config = array();
    private array $token = array();
    private string $tokenFile = '';
    private \GuzzleHttp\Client $client;
    private int $retries = 0;

    private array $orderData;

    public function Init(array $config)
    {
        $this->config = $config;
        $this->api_url = 'https://api-m.' . ($config['sandbox'] ? 'sandbox.' : '') . 'paypal.com/';
        $this->tokenFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'cm_paypaltoken_' . hash('crc32', $this->config['ClientID']) . '.json';
        //Try to load up the current token
        if (is_readable($this->tokenFile)) {
            $config_json = file_get_contents($this->tokenFile);
            //Double check if it really existed
            if ($config_json !== false) {
                $this->token = json_decode($config_json, true);
            } else {
                $this->token = array('expires' => time());
            }
        } else {
            $this->token = array('expires' => time());
        }
        //Set up the Guzzle client
        $this->client = new \GuzzleHttp\Client(['base_uri' => $this->api_url . 'v2/']);
        //Scaffold the Order Data
        $this->resetOrderData();
    }
    private function resetOrderData()
    {
        $this->orderData = array(
            'prep' => array(
                'items' => array(),
                'transaction_id'=>'',
                'invoice_id' => '',
                'description'=>'',
                'total'=>0.0,
                'discount'=>0.0,
                'return_urls'=>array(
                    'return_url'=>'',
                    'cancel_url'=>''
                )
            ),
            'stage'=>'init',
            'order_id'=>'',
            'inflight_data' => array()
        );
    }
    public function ProcessorOk(): bool
    {
        return $this->get_token() != '';
    }
    public function LoadOrder(string $data)
    {
        $this->orderData = json_decode($data, true);
        //Check the status of any in-progress order
        if (!empty($this->orderData['order_id'])) {
            try {
                $currentOrder = $this->api('checkout/orders/'.$this->orderData['order_id']);
                $this->orderData['inflight_data'] =  array_intersect_key($currentOrder, array_flip(array(
                    'id','status','links'
                )));
                $this->orderData['stage'] = $this->orderData['inflight_data']['status'];
                //If we happen to be complete, try and fetch HATEOAS links
                if (isset($currentOrder['purchase_units'][0]['payments']['captures'][0]['links'])) {
                    $this->orderData['inflight_data']['links'] = $currentOrder['purchase_units'][0]['payments']['captures'][0]['links'];
                }
            } catch (\Exception $e) {
            }
        }
    }
    public function SaveOrder(string &$data)
    {
        $data = json_encode($this->orderData);
    }
    public function SetOrderID(string $id)
    {
        $this->orderData['prep']['transaction_id'] = $id;
    }
    public function SetCustomerFacingID(string $id)
    {
        $this->orderData['prep']['invoice_id'] = $id;
    }
    public function SetOrderDescription(string $desc)
    {
        $this->orderData['prep']['description'] = substr($desc, 0, 127);
    }
    public function SetReturnURLs(string $payComplete, string $payCancel)
    {
        $this->orderData['prep']['return_urls'] = array(
            'return_url'=>$payComplete,
            'cancel_url'=>$payCancel
        );
    }
    public function GetDetails()
    {
        return array_intersect_key(
            $this->orderData,
            array_flip(array(
                'stage',
                'order_id'
            ))
        );
    }
    public function AddItem(string $name, float $amount, int $count = 1, ?string $description = null, ?string $sku = null, ?float $discount = null, ?string $discountReason = null)
    {
        $this->orderData['prep']['items'][] = array(
            'name'=>$name,
            'quantity'=>$count,
            //TODO: Maybe that's not always the right number format?
            'unit_amount'=> $this->makeMoney($amount),//number_format($amount, 2, '.', ''),
            'description'=>substr($description, 0, 127),
            'sku'=>$sku

        );
        //Add to the total
        $this->orderData['prep']['total'] += $amount * $count;
        //Are we discounting?
        if (!is_null($discount)) {
            $this->orderData['prep']['total'] -= $discount * $count;
            $this->orderData['prep']['discount'] += $discount * $count;
        }
    }
    public function ConfirmOrder(): bool
    {
        //Build up the order and ask PayPal to create it
        $orderResponse = $this->api('checkout/orders', $this->generateOrderData());
        //Note we expect a Created here, not an OK
        $this->orderData['inflight_data'] =$orderResponse;
        //Also for convenience
        $this->orderData['order_id'] = $this->orderData['inflight_data']['id'];
        $this->orderData['stage'] = $this->orderData['inflight_data']['status'];

        return true;
    }
    public function CancelOrder(): bool
    {
        //Have we already taken their money?
        if ($this->orderData['stage'] == 'COMPLETED') {
            return false;
        }
        //PayPal normal instant orders don't need cancellation, they'll just fall off after some time
        $this->orderData = array_merge($this->orderData, array(
            'stage'=>'init',
            'order_id'=>'',
            'inflight_data' => array()
        ));
        return true;
    }
    public function RetrievePaymentRedirectURL(): string
    {
        if ($this->orderData['stage'] != 'CREATED') {
            throw new \Exception('Order not in correct state? ' . $this->orderData['stage']);
        }
        return $this->getHATEOAS('approve');
    }
    public function CompleteOrder($data): bool
    {
        // //Ensure our ID is the same we're expecting
        // if ($data['token'] != $this->orderData['order_id']) {
        //     throw new \Exception('Token mismatch');
        // }
        //Did we already complete this?

        if ($this->orderData['stage'] == 'COMPLETED') {
            return true;
        }

        //Are we good to go?
        if ($this->orderData['stage'] == 'CREATED' || $this->orderData['stage'] == 'APPROVED') {
            try {
                $orderResponse = $this->api('checkout/orders/' . $this->orderData['order_id'] . '/capture', array('capture'=>true));
            } catch (RequestException $e) {
                if ($e->getCode() == 404) {
                    //It is no longer a thing, mark it disappeared
                    $this->orderData['stage'] = 'Expired';
                }

                return false;
            }


            //Note we expect a Created here, not an OK
            $this->orderData['inflight_data'] =$orderResponse;
            //Also for convenience
            $this->orderData['order_id'] = $this->orderData['inflight_data']['id'];
            $this->orderData['stage'] = $this->orderData['inflight_data']['status'];

            //Were we successful?
            if ($this->orderData['stage'] == 'COMPLETED') {
                //Merge down the capture HATEOAS because they're more useful there
                $this->orderData['inflight_data']['links'] = $this->orderData['inflight_data']['purchase_units'][0]['payments']['captures'][0]['links'];
                return true;
            }
        }
        return false;
    }
    public function GetOrderStatus(): string
    {
        $status = $this->orderData['stage'] ?? 'UNKNOWN';
        switch ($status) {
            case 'CREATED': return 'Incomplete';
            case 'COMPLETED': return 'Completed';
            case 'APPROVED': return 'Incomplete'; //Still need to confirm with PayPal

            case 'Expired': return 'NotStarted';
        }
        return 'NotStarted';
    }

    public function get_token()
    {
        if (count($this->token)>0) {
            //Check expiration
            if ($this->token['expires'] > time()) {
                return $this->token;
            }
        }

        $client = new \GuzzleHttp\Client(['base_uri' => $this->api_url . 'v1/']);
        $response = $client->request('POST', 'oauth2/token', array(
            'auth' => array($this->config['ClientID'] , $this->config['ClientSecret']),
            'form_params' => array(
                'grant_type' => 'client_credentials'
            )
        ));

        //Check if we're moderately successful
        $result =  json_decode($response->getBody()->getContents(), true);
        if ($response->getStatusCode() == 200) {
            $this->token =$result;
        } else {
            //Uh oh...
            throw new \Exception(
                'Failed to acquire PayPal token. Are credentials correct?',
                $response->getStatusCode(),
                new \Exception($result['error_description'])
            );
        }

        //Setup the expiration info
        $this->token['expires'] = $this->token['expires_in'] + time();
        //Write it out
        file_put_contents($this->tokenFile, json_encode($this->token));
        return $this->token;
    }


    public function api($method, $data = null)
    {
        //Ensure we are logged in
        if ($this->get_token() == '') {
            throw new \Exception("Not logged in, cannot " . $method);
        }
        //Set up the request
        $options = array(
            'headers' => array(
                'Authorization' => $this->token['token_type'] . ' ' . $this->token['access_token'],
                 'Accept'       => 'application/json'
            )
        );
        $verb = 'GET';
        if ($data) {
            $options['json'] = $data;
            $verb = 'POST';
        }

        //Execute it
        $response = $this->client->request($verb, $method, $options);

        //Were we successful?
        if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300) {
            //Yup, decode json and return!
            $this->retries = 0;
            return json_decode($response->getBody()->getContents(), true);
        }

        //Nope! Was it an unauthorized?
        if ($response->getStatusCode() == 401) {
            //We only want to try twice to re-auth
            if ($this->retries++ < 1) {
                $this->token['expires'] = time()-1;
                if ($this->get_token()['expires'] > time()) {
                    //Retry the request
                    return $this->api($method, $data);
                } else {
                    throw new \Exception(
                        'Failed to authenticate to PayPal. ',
                        4,
                        new \Exception($response->getBody()->getContents())
                    );
                }
            } else {
                //We authed OK but are still not allowed
                throw new \Exception(
                    'PayPal not authorized to ' . $method,
                    2,
                    new \Exception($response->getBody()->getContents())
                );
            }
        }

        //TODO: More handling
        throw new \Exception(
            'Failed to execute with PayPal trying to ' . $method,
            $response->getStatusCode(),
            new \Exception($response->getBody()->getContents())
        );
    }

    private function generateOrderData()
    {
        return array(
            'intent' =>'CAPTURE',
            'payer'=>array('payment_method'=>'paypal'),
            //'note_to_payer'=>$this->orderData['prep']['note']
            'purchase_units'=>array(
                array(
                    'amount'=>array_merge(
                        $this->makeMoney($this->orderData['prep']['total']),
                        array('breakdown'=>array(
                            'item_total' => $this->makeMoney($this->orderData['prep']['total'] + $this->orderData['prep']['discount']),
                            'discount' => $this->makeMoney($this->orderData['prep']['discount'])
                        ))
                    ),
                    'description' =>$this->orderData['prep']['description'],
                    'custom_id' =>$this->orderData['prep']['transaction_id'],
                    'invoice_id' =>$this->orderData['prep']['invoice_id'],
                    'items'=>$this->orderData['prep']['items']
                )
            ),
            'application_context'=>array(
                'return_url'=>$this->orderData['prep']['return_urls']['return_url'],
                'cancel_url'=>$this->orderData['prep']['return_urls']['cancel_url'],
            )

        );
    }
    private function makeMoney($value, $valueName = 'value', $currencyName = 'currency_code')
    {
        return array(
            $valueName =>number_format($value, 2, '.', ''),
            $currencyName=>$this->config['CurrencyType']
         );
    }
    private function getHATEOAS($linkName)
    {
        foreach ($this->orderData['inflight_data']['links'] as $link) {
            if ($link['rel'] == $linkName) {
                return $link['href'];
            }
        }
        throw new \Exception('HATEOAS link not found: ' . $linkName);
    }
}
