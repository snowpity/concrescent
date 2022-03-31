<?php

namespace CM3_Lib\Action\Public;

use CM3_Lib\Factory\PaymentModuleFactory;
use CM3_Lib\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Action.
 */
final class ListPayMethods
{
    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param eventinfo $eventinfo The service
     */
    public function __construct(private Responder $responder, private PaymentModuleFactory $PaymentModuleFactory)
    {
    }

    /**
     * Action.
     *
     * @param ServerRequestInterface $request The request
     * @param ResponseInterface $response The response
     *
     * @return ResponseInterface The response
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        // Extract the form data from the request query
        $qp = $request->getQueryParams();

        //TODO: This doesn't work because the /public route is obviated from authentication...
        //If we have permissions, allow the onsite parameter
        $perms = $request->getAttribute('perms');
        $isOnsite = false;
        if (isset($perms) && $perms->EventPerms->IsBadge_Checkin()) {
            $isOnsite = ($qp['onsite'] ?? 'false') == 'true';
        }


        // Invoke the Domain with inputs and retain the result
        $data = $this->PaymentModuleFactory->GetAvailableModules($isOnsite);

        // Build the HTTP response
        return $this->responder
            ->withJson($response, $data);
    }
}
