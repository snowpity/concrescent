<?php

namespace CM3_Lib\Middleware;

use CM3_Lib\util\PermEvent;
use CM3_Lib\util\PermGroup;
use CM3_Lib\util\EventPermissions;
use CM3_Lib\util\CurrentUserInfo;

use CM3_Lib\database\SearchTerm;
use CM3_Lib\models\application\group as g_group;

use CM3_Lib\Responder\Responder;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Exception\HttpUnauthorizedException;

class PermCheckGroupContextCode
{
    public array $AllowedPerms = array();
    public ?string $AttributeName = null;
    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param eventinfo $eventinfo The service
     */
    public function __construct(
        private g_group $g_group,
        private CurrentUserInfo $CurrentUserInfo
    ) {
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

        //Short circuit if we're global admin
        if ($perms->EventPerms->isGlobalAdmin()) {
            //Instant thumbs up
            return  $handler->handle($request);
        }

        //If we have an AttributeName, check it
        if (!is_null($this->AttributeName)) {
            if (isset($routeArguments[$this->AttributeName])) {
                $context_code = $routeArguments[$this->AttributeName];
                //Fetch group ID from context code
                $matchedGroups = $this->group->Search(['id'], [
                    $this->CurrentUserInfo->EventIdSearchTerm(),
                    new SearchTerm('context_code', $context_code),
                ]);
                $desiredGroupID = $matchedGroups[0] ?? 0;
                //Do they have permissions for the specified group at all?
                if (isset($perms->GroupPerms[$desiredGroupID])) {
                    //Are we checking for more than the group id at this time?
                    if (count($this->AllowedPerms) > 0) {
                        $gperms = $perms->GroupPerms[$desiredGroupID];
                        foreach ($this->AllowedPerms as $value) {
                            if ($value instanceof PermGroup) {
                                $hasPerm |= $gperms->getValue() & $value->getValue();
                            }
                        }
                    } else {
                        //We don't have any specific perms to check, pass this round
                        $hasPerm = true;
                    }
                }
            } else {
                throw new HttpInternalServerErrorException("PermCheckGroupId called but no argument <$this->AttributeName> to check against?");
            }
        }
        foreach ($this->AllowedPerms as $value) {
            if ($value instanceof PermEvent) {
                $hasPerm |= $perms->EventPerms->getValue() & $value->getValue();
            }
        }
        if (!$hasPerm) {
            throw new HttpUnauthorizedException($request, 'Group ID not accessible with current permissions');
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
