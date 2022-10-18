<?php

namespace CM3_Lib\Action\Application\Submission;

use CM3_Lib\models\application\submission;
use CM3_Lib\models\application\badgetype;

use CM3_Lib\util\badgeinfo;
use CM3_Lib\util\CurrentUserInfo;
use CM3_Lib\Modules\Notification\Mail;

use CM3_Lib\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

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
        private CurrentUserInfo $CurrentUserInfo,
        private badgeinfo $badgeinfo,
        private Mail $Mail
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
        $data['id'] = $params['id'];

        // Invoke the Domain with inputs and retain the result
        $data = $this->badgeinfo->UpdateSpecificBadgeUnchecked($params['id'], $params['context_code'], $data);

        //TODO: Use the notification framework for this...
        $badge = $this->badgeinfo->getSpecificBadge($data['id'], $params['context_code'], true);
        $to = $this->CurrentUserInfo->GetContactEmail($badge['contact_id']);
        $template = $params['context_code'] . '-application-' .$badge['application_status'];
        try {
            //Attempt to send mail
            $data['sentUpdate'] =  $this->Mail->SendTemplate($to, $template, $badge, $badge['notify_email']);
        } catch (\Exception $e) {
            //Oops, couldn't send. Oh well?
            $data['sentUpdate'] = false;
        }

        // Build the HTTP response
        return $this->responder
            ->withJson($response, $data);
    }
}
