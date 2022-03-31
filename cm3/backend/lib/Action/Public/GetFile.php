<?php

namespace CM3_Lib\Action\Public;

use CM3_Lib\util\BaseIntEncoder;

use CM3_Lib\database\SearchTerm;
use CM3_Lib\models\filestore;
use CM3_Lib\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpNotFoundException;

/**
 * Action.
 */
final class GetFile
{
    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param eventinfo $eventinfo The service
     */
    public function __construct(private Responder $responder, private filestore $filestore)
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
        //TODO: Actually do something with submitted data. Also, provide some sane defaults

        $params['id'] = BaseIntEncoder::decode($params['id']);

        $result = $this->filestore->GetByID($params['id'], array('event_id','date_modified','mimetype','visible'));
        if ($result === false || (isset($result['visible']) && $result['visible'] != 1)) {
            throw new HttpNotFoundException($request);
        }
        if ($result['event_id'] != $params['event_id']) {
            throw new HttpBadRequestException($request, 'Filestore item does not belong to the specified event!');
        }
        //Provide the HTTP-compliant modified time format
        $dateTime = new \DateTime($result['date_modified']);
        $dateTime->setTimezone(new \DateTimeZone('GMT'));
        $result['date_modified'] = $dateTime->format('D, d M Y H:i:s \G\M\T');

        $response = $response
        ->withHeader('Last-Modified', $result['date_modified']);

        //Check if we were asked about the modified time
        if ($request->hasHeader('If-Modified-Since')) {
            if (new \DateTime($request->getHeaderLine('If-Modified-Since'), new \DateTimeZone('UTC')) >= $dateTime) {
                return $response
                ->withStatus(304);
            }
        }

        if ($result['mimetype']) {
            $response = $response->withHeader('Content-Type', $result['mimetype']);
        } else {
            //We don't know, assume an octet stream...
            $response = $response->withHeader('Content-Type', 'application/octet-stream');
        }

        //Still here? Oh well, guess we gotta get the file proper then...
        $data = $this->filestore->GetByID($params['id'], array('data'))['data'];
        $response->getBody()->write($data);

        // Build the HTTP response
        return $response;
    }
}
