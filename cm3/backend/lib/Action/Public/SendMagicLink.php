<?php

namespace CM3_Lib\Action\Account;

use CM3_Lib\database\SearchTerm;

use CM3_Lib\models\contact;

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
    public function __construct(private Responder $responder, private contact $contact)
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
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, $params): ResponseInterface
    {
        $data = (array)$request->getParsedBody();

        // $mdb = new cm_mail_db($db);
        // $reviewlinks = $atdb->retrieve_attendee_reviewlinks($json['email']);
        // //echo json_encode($reviewlinks);
        // $template = $mdb->get_mail_template('attendee-retrieve');
        // foreach ($reviewlinks as $key => $item) {
        //   $mdb->send_mail($json['email'], $template, array('review-link' => $item));
        // }


        // Build the HTTP response
        return $this->responder
            ->withJson($response, $result);
    }
}
