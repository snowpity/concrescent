<?php

namespace CM3_Lib\Action\Badge\CheckIn;

use CM3_Lib\database\SelectColumn;
use CM3_Lib\database\View;
use CM3_Lib\database\Join;
use CM3_Lib\database\SearchTerm;

use CM3_Lib\util\badgeinfo;
use CM3_Lib\util\CurrentUserInfo;
use CM3_Lib\util\PaymentBuilder;
use CM3_Lib\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpNotFoundException;

/**
 * Action.
 */
final class Update
{
    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param eventinfo $eventinfo The service
     */
    public function __construct(
        private Responder $responder,
        private badgeinfo $badgeinfo,
        private PaymentBuilder $PaymentBuilder,
        private CurrentUserInfo $CurrentUserInfo,
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
        // Extract the form data from the request body
        $data = (array)$request->getParsedBody();
        $bid = $params['badge_id'];
        $cx = $params['context_code'];

        $current = $this->badgeinfo->GetSpecificBadge($bid, $cx, true);

        if ($current === false) {
            throw new HttpNotFoundException($request);
        }
        $data['uuid'] = $current['uuid'];
        $result = array();
        if ($current['payment_status'] == 'Completed' && $current['badge_type_id'] == $data['badge_type_id']) {
            $data['time_printed'] = null;
            $didUpdate = $this->badgeinfo->UpdateSpecificBadgeUnchecked(
                $bid,
                $cx,
                $data,
                array(
                    'real_name',
                    'fandom_name',
                    'name_on_badge',
                    'date_of_birth',
                    'notes'
                )
            );
        } else {
            //Our badge is incomplete or we're changing what type it is
            //First, load up the associated payment or create a new one
            $paymentLoaded = $this->PaymentBuilder->loadCart($current['payment_id']);
            //If we can't edit the cart, I guess we'll create a new one
            if ($paymentLoaded ===false || !$this->PaymentBuilder->canEdit()) {
                $this->PaymentBuilder->createCart($current['contact_id'], $this->CurrentUserInfo->GetContactName());
            }
            //Check if we have an item already
            $cartIx = $this->PaymentBuilder->findCartItemIxById($current['context_code'], $current['id']);
            if ($cartIx === false) {
                $cartIx =  (array_key_last($this->PaymentBuilder->getCartItems()) ?? -1)  +1;
                $data['existing'] = $current;
                $this->PaymentBuilder->setCartItem($cartIx, $data, $current['payment_promo_code'] ?? '');
            } else {
                //Slice in the submitted data into the existing item
                $item = array_merge($this->PaymentBuilder->getCartItemByIx($cartIx), $data);
                $this->PaymentBuilder->setCartItem($cartIx, $item, $current['payment_promo_code']);
            }
            $this->PaymentBuilder->resetPayment();
            $this->PaymentBuilder->saveCart();
        }

        //Refresh the resulting badge
        $result = $this->badgeinfo->GetSpecificBadge($bid, $cx, true);



        // Build the HTTP response
        return $this->responder
            ->withJson($response, $result);
    }
}
