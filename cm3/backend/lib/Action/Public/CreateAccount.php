<?php

namespace CM3_Lib\Action\Public;

use CM3_Lib\util\TokenGenerator;

use CM3_Lib\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Exception\HttpBadRequestException;

class CreateAccount
{
    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param eventinfo $eventinfo The service
     */
    public function __construct(private Responder $responder, private contact $contact, private eventinfo $eventinfo, private Branca $Branca)
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
        // Extract the form data from the request body
        $data = (array)$request->getParsedBody();
        $data['event_id'] = $data['event_id'] ?? null;

        //Check if there's an account already
        $existing = $this->contact->Search(null, array(new SearchTerm('email_address', $data['email_address'])), limit:1);
        if (count($existing) > 0) {
            throw new HttpBadRequestException('Contact already exists with that email.');
        }

        $result = $this->contact->Create($data);

        $result = $this->TokenGenerator->forUser(
            $result['id'],
            $data['event_id']
        );

        // Build the HTTP response
        return $this->responder
            ->withJson($response, $result);
    }
}
