<?php

namespace CM3_Lib\Middleware;

use CM3_Lib\util\PermEvent;
use CM3_Lib\util\EventPermissions;

use CM3_Lib\Responder\Responder;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Exception\HttpUnauthorizedException;

class PermCheckEventPerm
{
    public array $AllowedPerms = array();
    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param eventinfo $eventinfo The service
     */
    public function __construct()
    {
    }
    /**
     * Example middleware invokable class
     *
     * @param  ServerRequest  $request PSR-7 request
     * @param  RequestHandler $handler PSR-15 request handler
     *
     * @return Response
     */
    public function __invoke(Request $request, RequestHandler $handler): \Nyholm\Psr7\Response
    {
        $perms = $request->getAttribute('perms');
        $hasPerm = false;

        foreach ($this->AllowedPerms as $value) {
            if ($value instanceof PermEvent) {
                $hasPerm |= $perms->EventPerms->getValue() & $value->getValue();
            }
        }
        if (!$hasPerm && !$perms->EventPerms->isGlobalAdmin()) {
            throw new HttpUnauthorizedException($request, 'Not accessible with current permissions');
        }
        return  $handler->handle($request);
    }
    public function withAllowedPerms(array $perms)
    {
        $new = clone $this;
        $new->AllowedPerms = $perms;
        return $new;
    }
    public function withAllowedPerm(PermEvent $permToAdd)
    {
        $new = clone $this;
        $new->AllowedPerms[] = $permToAdd;
        return $new;
    }
}
