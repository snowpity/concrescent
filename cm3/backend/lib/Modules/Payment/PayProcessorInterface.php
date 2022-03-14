<?php

namespace CM3_Lib\Modules\Payment;

/**
 * Things every payment processor should be able to do
 */
interface PayProcessorInterface
{
    public function Init(array $config);
    public function ProcessorOk(): bool;
    //Load order state
    public function LoadOrder(string $data);
    //Save order state
    public function SaveOrder(string &$data);
    //Set internal ID if not provided by the processor
    public function SetOrderID(string $id);
    //Set customer-facing ID (i.e. on the receipt)
    public function SetCustomerFacingID(string $id);
    //Order description
    public function SetOrderDescription(string $desc);
    //Provide return URL for post-payment
    public function SetReturnURLs(string $payComplete, string $payCancel);
    //Details about the current order state
    public function GetDetails();
    //Add an item to the order
    public function AddItem(string $name, float $amount, int $count = 1, ?string $description = null, ?string $sku = null, ?float $discount = null, ?string $discountReason = null);
    //Compile the order and make sure it's ready to be processed
    public function ConfirmOrder(): bool;
    //Close order if it's in-flight to prevent possible double-charging
    public function CancelOrder(): bool;
    //If processor needs the user to visit someplace, what is the URL?
    public function RetrievePaymentRedirectURL(): string;
    //The user has come back with order completion information, let's do it!
    public function CompleteOrder($data): bool;
    //payment_status translation
    public function GetOrderStatus(): string;
}
