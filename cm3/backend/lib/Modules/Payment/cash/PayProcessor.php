<?php

use CM3_Lib\util\CurrentUserInfo;
class PayProcessor implements \CM3_Lib\Modules\Payment\PayProcessorInterface
{
    public __construct(
    private CurrentUserInfo $CurrentUserInfo,)
    private $orderstate = array();
    private bool $isDisabled = false;
    public function Init(array $config)
    {
    }

    private function resetOrderData()
    {
        $this->orderData = array(
            'total'     => 0.0,
            'discount'  => 0.0,
            'tax_total' => 0.0,
            'items'     => array(),
            'stage' => 'init',
            'handler_id' => 0
        );
    }
    public function ProcessorOk(): bool
    {
        return !$this->isDisabled;
    }
    public function LoadOrder(string $data)
    {
        $this->orderData = json_decode($data, true);
    }
    public function SaveOrder(string &$data)
    {
        $data = json_encode($this->orderData);
    }
    public function SetOrderID(string $id)
    {
    }
    public function SetCustomerFacingID(string $id)
    {
    }
    public function SetOrderDescription(string $desc)
    {
    }
    public function SetReturnURLs(string $payComplete, string $payCancel)
    {
        //Not really applicable
    }
    public function GetDetails()
    {
        return $this->orderstate;
    }
    public function AddItem(string $name, float $amount, int $count = 1, ?string $description, ?float $discount, ?string $discountReason)
    {
        $this->orderstate['items'][] = array(
            'name' => $name,
            'amount' => $amount,
            'count' => $count,
            'description'=>$description,
            'discount'=>$discount,
            'discountReason'=>$discountReason
        );
        $this['total'] += ($amount - $discount) * $count;
        $this['discount'] += $discount * $count;
    }
    public function ConfirmOrder(): bool
    {
        return true;
    }
    public function RetrievePaymentRedirectURL(): string
    {
        return '';
    }
    public function CompleteOrder($data): bool
    {
        //We assume that physical cash has been handled.
        //Mark the payment for who triggered this action
        $this->orderData['handler_id'] = $this->CurrentUserInfo->GetContactId();
        return true;
    }
    public function GetOrderStatus(): string
    {
        $status = $this->orderData['stage'] ?? 'UNKNOWN';
        switch ($status) {
            case 'CREATED': return 'Incomplete';
            case 'COMPLETED': return 'Completed';
            case 'APPROVED': return 'Incomplete'; //Still need to confirm with PayPal

        }
        return 'NotStarted';
    }
}
