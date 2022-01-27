<?php

namespace CM3_Lib\Responder;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Slim\Interfaces\RouteParserInterface;
use function http_build_query;

/**
 * A generic responder.
 */
final class Responder
{
    private RouteParserInterface $routeParser;

    private ResponseFactoryInterface $responseFactory;

    /**
     * The constructor.
     *
     * @param RouteParserInterface $routeParser The route parser
     * @param ResponseFactoryInterface $responseFactory The response factory
     */
    public function __construct(
        RouteParserInterface $routeParser,
        ResponseFactoryInterface $responseFactory
    ) {
        $this->responseFactory = $responseFactory;
        $this->routeParser = $routeParser;
    }

    /**
     * Create a new response.
     *
     * @return ResponseInterface The response
     */
    public function createResponse(): ResponseInterface
    {
        return $this->responseFactory->createResponse()->withHeader('Content-Type', 'text/html; charset=utf-8');
    }

    /**
     * Creates a redirect for the given url / route name.
     *
     * This method prepares the response object to return an HTTP Redirect
     * response to the client.
     *
     * @param ResponseInterface $response The response
     * @param string $destination The redirect destination (url or route name)
     * @param array $queryParams Optional query string parameters
     *
     * @return ResponseInterface The response
     */
    public function withRedirect(
        ResponseInterface $response,
        string $destination,
        array $queryParams = []
    ): ResponseInterface {
        if ($queryParams) {
            $destination = sprintf('%s?%s', $destination, http_build_query($queryParams));
        }

        return $response->withStatus(302)->withHeader('Location', $destination);
    }

    /**
     * Creates a redirect for the given url / route name.
     *
     * This method prepares the response object to return an HTTP Redirect
     * response to the client.
     *
     * @param ResponseInterface $response The response
     * @param string $routeName The redirect route name
     * @param array $data Named argument replacement data
     * @param array $queryParams Optional query string parameters
     *
     * @return ResponseInterface The response
     */
    public function withRedirectFor(
        ResponseInterface $response,
        string $routeName,
        array $data = [],
        array $queryParams = []
    ): ResponseInterface {
        return $this->withRedirect($response, $this->routeParser->urlFor($routeName, $data, $queryParams));
    }

    /**
     * Write JSON to the response body.
     *
     * This method prepares the response object to return an HTTP JSON
     * response to the client.
     *
     * @param ResponseInterface $response The response
     * @param mixed $data The data
     * @param int $options Json encoding options
     *
     * @return ResponseInterface The response
     */
    public function withJson(
        ResponseInterface $response,
        $data = null,
        int $options = 0
    ): ResponseInterface {
        $response = $response->withHeader('Content-Type', 'application/json');
        $response->getBody()->write((string)json_encode($data, $options));

        return $response;
    }
}
