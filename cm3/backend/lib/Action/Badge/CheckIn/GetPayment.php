<?php

namespace CM3_Lib\Action\Badge\CheckIn;

use CM3_Lib\database\SearchTerm;

use CM3_Lib\util\PaymentBuilder;
use CM3_Lib\models\payment;
use CM3_Lib\util\badgevalidator;
use CM3_Lib\util\CurrentUserInfo;

use Branca\Branca;
use MessagePack\MessagePack;
use MessagePack\Packer;

use CM3_Lib\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

use Slim\Exception\HttpNotFoundException;

class GetPayment
{
    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param eventinfo $eventinfo The service
     */
    public function __construct(
        private Responder $responder,
        private PaymentBuilder $PaymentBuilder,
        private badgevalidator $badgevalidator,
        private CurrentUserInfo $CurrentUserInfo
    ) {
    }

    /**
     * Action.
     *
     * @param ServerRequestInterface $request The request
     * @param ResponseInterface $response The response
     *
     * @return ResponseInterface The response
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, $params): ResponseInterface
    {
        $data = (array)$request->getQueryParams();

        if (!$this->PaymentBuilder->loadCartFromBadge($params['context_code'], $params['badge_id'])) {
            throw new HttpNotFoundException($request);
        }

        $result = array();
        $result['items']= $this->PaymentBuilder->getCartItems();
        $result['errors']= $this->PaymentBuilder->getCartErrors(false);
        $result['state']= $this->PaymentBuilder->getCartStatus();
        $result['total']= $this->PaymentBuilder->getCartTotal();


        // Build the HTTP response
        return $this->responder
            ->withJson($response, $result);
    }
}
