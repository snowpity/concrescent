<?php

namespace CM3_Lib\Middleware;

use CM3_Lib\util\PermEvent;
use CM3_Lib\util\EventPermissions;
use CM3_Lib\util\CurrentUserInfo;

use CM3_Lib\Responder\Responder;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Exception\HttpUnauthorizedException;

class PermCheckEventId
{
    public array $AllowedPerms = array();
    public ?string $AttributeName = null;
    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param eventinfo $eventinfo The service
     */
    public function __construct(private CurrentUserInfo $CurrentUserInfo)
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
        $routeArguments = \Slim\Routing\RouteContext::fromRequest($request)->getRoute()->getArguments();
        $perms = $request->getAttribute('perms');
        $hasPerm = false;

        //If we have an AttributeName, check it
        if (!is_null($this->AttributeName)) {
            if (isset($routeArguments[$this->AttributeName])) {
                if ($routeArguments[$this->AttributeName] != $this->CurrentUserInfo->GetEventId()&& !$perms->EventPerms->isGlobalAdmin()) {
                    throw new HttpUnauthorizedException($request, 'Event ID not accessible from current login');
                } else {
                    $hasPerm = true;
                }
            } else {
                //error_log("PermCheckEventId called but no argument <$this->AttributeName> to check against?");
            }
        }
        foreach ($this->AllowedPerms as $value) {
            if ($value instanceof PermEvent) {
                $hasPerm |= $perms->EventPerms->getValue() & $value->getValue();
            }
        }
        if (!$hasPerm && !$perms->EventPerms->isGlobalAdmin()) {
            throw new HttpUnauthorizedException($request, 'Event ID not accessible with current permissions');
        }
        return  $handler->handle($request);
    }

    public function withAttributeName(string $inAttributeName)
    {
        $new = clone $this;
        $new->AttributeName = $inAttributeName;
        return $new;
    }
    public function withAllowedPerms(array $perms)
    {
        $new = clone $this;
        $new->AllowedPerms = $perms;
        return $new;
    }
    public function withAllowedPerm(PermGroup $permToAdd)
    {
        $new = clone $this;
        $new->AllowedPerms[] = $permToAdd;
        return $new;
    }
}
