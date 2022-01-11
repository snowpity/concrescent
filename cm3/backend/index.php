<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Exception\HttpNotFoundException;
use Slim\Factory\AppFactory;
use Slim\Routing\RouteContext;

require __DIR__ . '/vendor/autoload.php';

//Setup config
global $s_config;
$s_config = include('config.php');


$app = AppFactory::create();

$app->setBasePath('/concrescent/cm3/backend');

//TODO: Maybe do CORS correctly instead of catch-all?
$app->options('/{routes:.+}', function ($request, $response, $args) {
    return $response;
});
$app->add(function ($request, $handler) {
    $methods = RouteContext::fromRequest($request)->getRoutingResults()->getAllowedMethods();

    $response = $handler->handle($request);
    return $response
            ->withHeader('Access-Control-Allow-Origin', '*')//Detect?
            ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
            ->withHeader('Access-Control-Allow-Methods', implode(",", $methods));
    ;
});


/*
 * The routing middleware should be added earlier than the ErrorMiddleware
 * Otherwise exceptions thrown from it will not be handled by the middleware
 */
$app->addRoutingMiddleware();

//post body middleware
$app->addBodyParsingMiddleware();

// Gzip compression middleware
$compress = function (Request $request, RequestHandler $handler) {
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
};
$app->add($compress);

/*
 *
 * @param bool $displayErrorDetails -> Should be set to false in production
 * @param bool $logErrors -> Parameter is passed to the default ErrorHandler
 * @param bool $logErrorDetails -> Display error details in error log
 * which can be replaced by a callable of your choice.
 *
 * Note: This middleware should be added last. It will not handle any exceptions/errors
 * for middleware added after it.
 */
$errorMiddleware = $app->addErrorMiddleware(true, true, true);

//Todo: Implement more advanced error handling (i.e. 404)?
//https://www.slimframework.com/docs/v4/middleware/error-handling.html
//https://github.com/slimphp/Slim/issues/2827


// Routes:
// //auth
// $app->group('/auth', function (Slim\Routing\RouteCollectorProxy $group) {
//     $group->post('', \bw\action\authController::class . ':login');
// });
// $app->post('/limittoken', \bw\action\authController::class . ':limited');
//
//
// //mapping
// $app->group('/metaMap/{bwSchemaVersion}', function (Slim\Routing\RouteCollectorProxy $group) {
//     $group->get('', \bw\action\metaMappingController::class . ':get');
//     $group->put('', \bw\action\metaMappingController::class . ':put');
// });

//Default root
$app->get('/', function (Request $request, Response $response, $args) {
    //Todo: List available routes according to permission
    $response->getBody()->write("You need to specify a route...");
    return $response;
});


/*
 * Catch-all route to serve a 404 Not Found page if none of the routes match
 * NOTE: make sure this route is defined last
 */
$app->map(['GET', 'POST', 'PUT', 'DELETE', 'PATCH'], '/{routes:.+}', function ($request, $response) {
    throw new HttpNotFoundException($request);
});


$app->run();
