<?php

namespace CM3_Lib\Action\Public;

use CM3_Lib\database\SearchTerm;

use CM3_Lib\models\contact;
use CM3_Lib\Modules\Notification\Mail;
use CM3_Lib\util\TokenGenerator;
use CM3_Lib\util\FrontendUrlTranslator;
use CM3_Lib\util\CurrentUserInfo;

use Branca\Branca;
use MessagePack\MessagePack;
use MessagePack\Packer;

use CM3_Lib\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class SendMagicLink
{
    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param eventinfo $eventinfo The service
     */
    public function __construct(
        private Responder $responder,
        private contact $contact,
        private TokenGenerator $TokenGenerator,
        private Mail $Mail,
        private FrontendUrlTranslator $FrontendUrlTranslator,
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
    public function __invoke(
        ServerRequestInterface $request,
        ResponseInterface $response,
        $params
    ): ResponseInterface {
        $data = (array) $request->getParsedBody();

        //Confirm event_id is valid
        $data['event_id'] = $this->TokenGenerator->checkEventID($data['event_id'] ?? null);
        //Let our session know of the possible event change
        $this->CurrentUserInfo->SetEventId($data['event_id']);

        $contact = $this->contact->Search(
            ["id", "uuid", "email_address", "real_name"],
            [
                new SearchTerm(
                    "email_address",
                    $data["email_address"],
                    EncapsulationFunction: "lower(?)"
                ),
            ]
        );

        if ($contact !== false && count($contact) > 0) {
            $contact = $contact[0];
            //Add in necessary info
            $contact["login_url"] = $this->FrontendUrlTranslator->GetLoginConfirm($this->TokenGenerator->forLoginOnly(
                $contact["id"],
                $data["event_id"]
            ));

            if (
                $this->Mail->SendTemplate(
                    $data["email_address"],
                    "login-link",
                    $contact
                )
            ) {
                $result = "Sent.";
            } else {
                throw new \Exception('Failed to send message.', 0, new \Exception($this->Mail->getMailerErrorInfo()));
            }
        } else {
            $result = "Sent";
        }

        // Build the HTTP response
        return $this->responder->withJson($response, $result);
    }
}
