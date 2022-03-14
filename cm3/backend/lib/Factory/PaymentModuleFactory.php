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
        switch ($moduleName) {
            case 'PayPal':
            $result = new \CM3_Lib\Modules\Payment\paypal\PayProcessor();
        }
        if (!is_null($result)) {
            $result->Init($this->config[$moduleName]);
            return $result;
        }
        return null;
    }
}
