<?php

class PayProcessor implements \CM3_Lib\Modules\Payment\PayProcessorInterface
{
    private $orderstate = array();
    private bool $isDisabled = false;
    public function Init(array $config)
    {
        $orderstate = array(
            'total'     => 0.0,
            'discount'  => 0.0,
            'tax_total' => 0.0,
            'items'     => array()
        )
    }
    public function ProcessorOk(): bool
    {
        return !$this->isDisabled;
    }
    public function LoadOrder($data)
    {
        $orderstate = $data;
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
        return false;
    }
    public function RetrievePaymentRedirectURL(): string
    {
        return '';
    }
    public function CompleteOrder($data): bool
    {
        return true;
    }
    public function GetOrderStatus() : string
    {
        
    }
}
