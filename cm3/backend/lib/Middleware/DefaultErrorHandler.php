<?php

namespace CM3_Lib\Middleware;

use CM3_Lib\Factory\LoggerFactory;
use CM3_Lib\Responder\Responder;
use CM3_Lib\util\CurrentUserInfo;
use DomainException;
use Fig\Http\Message\StatusCodeInterface;
use InvalidArgumentException;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Slim\Exception\HttpException;
use Slim\Interfaces\ErrorHandlerInterface;
use Throwable;

/**
 * Default Error Renderer.
 */
final class DefaultErrorHandler implements ErrorHandlerInterface
{
    private Responder $responder;

    private ResponseFactoryInterface $responseFactory;

    private LoggerInterface $logger;

    private string $installpath;
    private CurrentUserInfo $CurrentUserInfo;
    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param ResponseFactoryInterface $responseFactory The response factory
     * @param LoggerFactory $loggerFactory The logger factory
     */
    public function __construct(
        Responder $responder,
        ResponseFactoryInterface $responseFactory,
        LoggerFactory $loggerFactory,
        CurrentUserInfo $CurrentUserInfo
    ) {
        $this->responder = $responder;
        $this->responseFactory = $responseFactory;
        $this->logger = $loggerFactory->createLogger('Main');
        $this->installpath = dirname(__DIR__, 2);
        $this->CurrentUserInfo = $CurrentUserInfo;
    }

    /**
     * Invoke.
     *
     * @param ServerRequestInterface $request The request
     * @param Throwable $exception The exception
     * @param bool $displayErrorDetails Show error details
     * @param bool $logErrors Log errors
     * @param bool $logErrorDetails Log error details
     *
     * @return ResponseInterface The response
     */
    public function __invoke(
        ServerRequestInterface $request,
        Throwable $exception,
        bool $displayErrorDetails,
        bool $logErrors,
        bool $logErrorDetails
    ): ResponseInterface {
        // Log error
        if ($logErrors) {
            $error = $this->getErrorDetails($exception, $logErrorDetails);
            $error['method'] = $request->getMethod();
            $error['url'] = (string)$request->getUri();
            $error['contact_id'] = $this->CurrentUserInfo->GetContactId();
            $error['event_id'] = $this->CurrentUserInfo->GetEventId();

            $this->logger->error($exception->getMessage(), $error);
        }

        $response = $this->responseFactory->createResponse();

        // Render response
        $response = $this->responder->withJson($response, [
            'error' => $this->getErrorDetails($exception, $displayErrorDetails),
        ]);

        return $response->withStatus($this->getHttpStatusCode($exception));
    }

    /**
     * Get http status code.
     *
     * @param Throwable $exception The exception
     *
     * @return int The http code
     */
    private function getHttpStatusCode(Throwable $exception): int
    {
        // Detect status code
        $statusCode = StatusCodeInterface::STATUS_INTERNAL_SERVER_ERROR;

        if ($exception instanceof HttpException) {
            $statusCode = (int)$exception->getCode();
        }

        if ($exception instanceof DomainException || $exception instanceof InvalidArgumentException) {
            // Bad request
            $statusCode = StatusCodeInterface::STATUS_BAD_REQUEST;
        }

        $file = basename($exception->getFile());
        if ($file === 'CallableResolver.php') {
            $statusCode = StatusCodeInterface::STATUS_NOT_FOUND;
        }

        return $statusCode;
    }

    /**
     * Get error message.
     *
     * @param Throwable $exception The error
     * @param bool $displayErrorDetails Display details
     *
     * @return array The error details
     */
    private function getErrorDetails(Throwable $exception, bool $displayErrorDetails): array
    {
        if ($displayErrorDetails === true) {
            return [
                'message' => $exception->getMessage(),
                'code' => $exception->getCode(),
                'file' =>  substr($exception->getFile(), strlen($this->installpath)),
                'line' => $exception->getLine(),
                'previous' => $exception->getPrevious(),
                'trace' => $this->cleanTrace($exception->getTrace()),
            ];
        }

        return [
            'message' => $exception->getMessage(),
        ];
    }

    private function cleanTrace(array $inTrace)
    {
        $result = array();
        foreach ($inTrace as $key => $trace) {
            //Trim install path from the file name
            if (isset($trace['file']) && str_starts_with($trace['file'], $this->installpath)) {
                $trace['file'] = substr($trace['file'], strlen($this->installpath));
            }
            //Trim install path from class name
            if (isset($trace['class'])) {
                $trace['class'] = str_replace("\0".$this->installpath, '::', $trace['class']);
            }

            $result[$key] = $trace;
        }
        return $result;
    }
}
