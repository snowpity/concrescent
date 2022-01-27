<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Factory\AppFactory;
use Slim\Routing\RouteContext;

require __DIR__ . '/vendor/autoload.php';

//Build up the container instance

$container = (new \CM3_Lib\Factory\ContainerFactory())->createInstance();

//And run it
$container->get(\Slim\App::class)->run();
