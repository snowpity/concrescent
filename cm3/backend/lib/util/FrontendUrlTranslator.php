<?php

namespace CM3_Lib\util;

use CM3_Lib\database\SearchTerm;

class FrontendUrlTranslator
{
    public function __construct(
        private string $frontend_host,
        private bool $isHashMode
    ) {
    }

    public function routedURL(string $route)
    {
        return $this->frontend_host . ($this->isHashMode ? '#/' : '') . $route;
    }

    public function GetPaymentReturn()
    {
        return $this->routedURL('checkout?confirm');
    }
    public function GetPaymentCancel()
    {
        return $this->routedURL('checkout?cancel');
    }
    public function GetBadgeLoad(array $badge)
    {
        return $this->routedURL('myBadges?id=' . $badge['id'] . '&uuid=' . $badge['uuid']);
    }
    public function GetLoginConfirm(string $authString)
    {
        return $this->routedURL('login?token=' . $authString);
    }
}
