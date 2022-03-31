<?php

namespace CM3_Lib\Factory;

class PaymentModuleFactory
{
    public function __construct(private array $config)
    {
    }
    public function Create(string $moduleName)
    {
        $result = null;
        //TODO: Implement dynamic detection
        switch ($moduleName) {
            case 'PayPal':
            $result = new \CM3_Lib\Modules\Payment\paypal\PayProcessor();
            break;
            case 'Cash':
            $result = new \CM3_Lib\Modules\Payment\cash\PayProcessor();
            break;
        }
        if (!is_null($result)) {
            $result->Init($this->config[$moduleName]);
            return $result;
        }
        return null;
    }

    public function GetAvailableModules(bool $onsite = false)
    {
        //TODO: Implement dynamic detection
        $result = array();
        if ($onsite) {
            $result[] = "Cash";
        }
        $result[] = "PayPal";

        return $result;
    }
}
