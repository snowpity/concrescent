<?php

use CM3_Lib\Factory\LoggerFactory;
use CM3_Lib\Factory\PaymentModuleFactory;
use CM3_Lib\Middleware\DefaultErrorHandler;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UploadedFileFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;
use Slim\App;
use Slim\Factory\AppFactory;
use Slim\Interfaces\RouteParserInterface;
use Slim\Middleware\ErrorMiddleware;
use Slim\Exception\HttpNotFoundException;

use Branca\Branca;
use CM3_Lib\database\DbConnection;

use CM3_Lib\Middleware\PermCheckEventId;

return [
    // Application settings
    'config' => function () {
        return require __DIR__ . '/../config.php';
    },

    App::class => function (ContainerInterface $container) {
        $app = AppFactory::createFromContainer($container);

        // Register routes, up to three folders deep
        foreach (glob(__DIR__ . '/../routes/{,*/,*/*/,*/*/*/}*.php', GLOB_BRACE) as $route) {
            (require $route)($app, $container);
        }

        /*
         * Catch-all route to serve a 404 Not Found page if none of the routes match
         * NOTE: make sure this route is defined last
         */
        $app->map(['GET', 'POST', 'PUT', 'DELETE', 'PATCH'], '/{routes:.+}', function ($request, $response) {
            throw new HttpNotFoundException($request);
        });


        // Register middleware
        (require __DIR__ . '/middleware.php')($app, $container->get('config'));

        return $app;
    },

    // HTTP factories
    ResponseFactoryInterface::class => function (ContainerInterface $container) {
        return $container->get(Psr17Factory::class);
    },

    ServerRequestFactoryInterface::class => function (ContainerInterface $container) {
        return $container->get(Psr17Factory::class);
    },

    StreamFactoryInterface::class => function (ContainerInterface $container) {
        return $container->get(Psr17Factory::class);
    },

    UploadedFileFactoryInterface::class => function (ContainerInterface $container) {
        return $container->get(Psr17Factory::class);
    },

    UriFactoryInterface::class => function (ContainerInterface $container) {
        return $container->get(Psr17Factory::class);
    },

    // The Slim RouterParser
    RouteParserInterface::class => function (ContainerInterface $container) {
        return $container->get(App::class)->getRouteCollector()->getRouteParser();
    },

    // The logger factory
    LoggerFactory::class => function (ContainerInterface $container) {
        return (new LoggerFactory($container->get('config')['logger']))
        ->addDBHandler($container->get(\CM3_Lib\models\admin\access_log::class))
        ->addFileHandler('access.log')
        ;
    },
    //And one for errors specifically
    'ErrorLoggerFactory'=> function (ContainerInterface $container) {
        return (new LoggerFactory($container->get('config')['logger']))
        ->addDBHandler($container->get(\CM3_Lib\models\admin\error_log::class))
        ->addFileHandler('error.log');
    },

    BasePathMiddleware::class => function (ContainerInterface $container) {
        return new BasePathMiddleware($container->get(App::class));
    },

    // Database connection
    DbConnection::class => function (ContainerInterface $container) {
        return new DbConnection($container->get('config')['database']);
    },

    //Auth signer
    Branca::class => function (ContainerInterface $container) {
        return new Branca($container->get('config')['environment']['token_secret']);
    },

    PaymentModuleFactory::class => function (ContainerInterface $container) {
        return new PaymentModuleFactory($container->get('config')['payments']);
    },

    ErrorMiddleware::class => function (ContainerInterface $container) {
        $s_config_error = $container->get('config')['error'];
        $app = $container->get(App::class);

        $errorMiddleware = new ErrorMiddleware(
            $app->getCallableResolver(),
            $app->getResponseFactory(),
            (bool)$s_config_error['display_error_details'],
            (bool)$s_config_error['log_errors'],
            (bool)$s_config_error['log_error_details']
        );

        $errorMiddleware->setDefaultErrorHandler(
            //We're overriding the normal logger factory in the autowire here
            $container->make(DefaultErrorHandler::class, array('loggerFactory'=> $container->get('ErrorLoggerFactory')))
        );

        return $errorMiddleware;
    }
];
