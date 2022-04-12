<?php

namespace CM3_Lib\Action\Badge\CheckIn;

use CM3_Lib\database\SearchTerm;
use CM3_Lib\Factory\PaymentModuleFactory;
use CM3_Lib\util\PaymentBuilder;
use CM3_Lib\util\CurrentUserInfo;

use CM3_Lib\models\payment;

use CM3_Lib\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpNotFoundException;

class PostPayment
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
        $data = (array)$request->getParsedBody();

        if (!$this->PaymentBuilder->loadCartFromBadge($params['context_code'], $params['badge_id'])) {
            throw new HttpNotFoundException($request);
        }

        if ($this->PaymentBuilder->getCartStatus() == 'Incomplete') {
            //Hrm, they've already initiated the payment request. Check if it's completed

            if ($this->PaymentBuilder->CompletePayment($data)) {
                //Sweet!
                $this->PaymentBuilder->SendStatusEmail();
                // Build the HTTP response
                return $this->responder
                ->withJson($response, array(
                    'state' => $this->PaymentBuilder->getCartStatus()
                ));
            } else {
                //Nope. Let us fall through and reset it
            }
        } elseif ($this->PaymentBuilder->getCartStatus() == 'Cancelled') {
            //They want to try paying again after cancelling
            $this->PaymentBuilder->CancelPayment();
        }

        //if (isset($data['payment_system'])) {
        $this->PaymentBuilder->setRequestedBy($this->CurrentUserInfo->GetContactName());
        $this->PaymentBuilder->setPayProcessor($data['payment_system']);
        // } elseif (empty($this->PaymentBuilder->getPayProcessorName())) {
        //     throw new \Exception('payment_system not specified!');
        // }

        //Build the payment
        $errors = $this->PaymentBuilder->prepPayment();

        if (count($errors) > 0) {
            throw new \Exception('Errors! ' . var_dump($errors));
        }
        //Finish the prep
        if ($this->PaymentBuilder->confirmPrep()) {
            //We assume they're instantly paid because they're at the checkin path
            if ($this->PaymentBuilder->CompletePayment($data)) {
                $this->PaymentBuilder->SendStatusEmail();
                // Build the HTTP response
                return $this->responder
                    ->withJson($response, array(
                        'state' => $this->PaymentBuilder->getCartStatus()
                    ));
            } else {
                //Something went wrong, let the screen know the next step
                return $this->responder
                ->withJson($response, array(
                    'paymentURL' => $this->PaymentBuilder->getPayProcessor()->RetrievePaymentRedirectURL(),
                    'state' => $this->PaymentBuilder->getCartStatus()
                ));
            }
        }
        // Build the HTTP response
        return $this->responder
            ->withJson($response, $result);
    }
}
