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

    public function GetPaymentReturn(string $cartId = '')
    {
        return $this->routedURL('cart?checkout=confirm&cartId=' . $cartId);
    }
    public function GetPaymentCancel(string $cartId = '')
    {
        return $this->routedURL('cart?checkout=cancel&cartId=' . $cartId);
    }
    public function GetBadgeLoad(array $badge)
    {
        return $this->routedURL('myBadges?context_code='. $badge['context_code'] . '&id=' . $badge['id'] . '&uuid=' . $badge['uuid']);
    }
    public function GetLoginConfirm(string $authString)
    {
        return $this->routedURL('login?token=' . $authString);
    }
}
