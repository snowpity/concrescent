<?php

namespace CM3_Lib\Middleware;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Server\MiddlewareInterface;

//TODO: This is likely broken
class GZCompress implements MiddlewareInterface
{
    /**
     * @var ContainerInterface|null
     */
    protected $handlerContainer;

    /**
     * @var ResponseFactoryInterface
     */
    private $responseFactory;


    public function __construct(
        ResponseFactoryInterface $responseFactory
    ) {
        $this->responseFactory = $responseFactory;
    }
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        //* This implicit compression handling doesn't work?
        ini_set("zlib.output_compression", 4096);
        $response = $handler->handle($request);
        return $response
         ->withHeader('Content-Length', $response->getBody()->getSize());
        //*/
        if ($request->hasHeader('Accept-Encoding') &&
         stristr($request->getHeaderLine('Accept-Encoding'), 'gzip') === false
     ) {
            // Browser doesn't accept gzip compression
            return $handler->handle($request);
        }

        /** @var Response $response */
        $response = $handler->handle($request);

        if ($response->hasHeader('Content-Encoding')) {
            return $handler->handle($request);
        }

        // Compress response data
        $deflateContext = deflate_init(ZLIB_ENCODING_GZIP);
        $compressed = deflate_add($deflateContext, (string)$response->getBody(), \ZLIB_FINISH);

        $stream = fopen('php://memory', 'r+');
        fwrite($stream, $compressed);
        rewind($stream);

        return $response
         ->withHeader('Content-Encoding', 'gzip')
         ->withHeader('Content-Length', strlen($compressed))
         ->withBody(new \Slim\Psr7\Stream($stream));
    }
};
