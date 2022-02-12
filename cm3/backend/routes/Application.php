<?php

// Define app routes
use CM3_Lib\util\PermEvent;
use CM3_Lib\util\PermGroup;
use CM3_Lib\Middleware\PermCheckGroupId;

use Slim\App;
use Slim\Routing\RouteCollectorProxy;
use Slim\Routing\RouteContext;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

return function (App $app, $container) {
    $groupPerm = $container->get(PermCheckGroupId::class);

    $r = array(
        '/BadgeType' => function (RouteCollectorProxy $app) use ($groupPerm) {
            $gpAsign =$groupPerm->withAllowedPerm(PermGroup::Badge_Manage());
            $app->get('', \CM3_Lib\Action\Application\BadgeType\Search::class)
            ->add($gpAsign);
            $app->post('', \CM3_Lib\Action\Application\BadgeType\Create::class)
            ->add($gpAsign);
            $app->get('/{id}', \CM3_Lib\Action\Application\BadgeType\Read::class)
            ->add($gpAsign);
            $app->post('/{id}', \CM3_Lib\Action\Application\BadgeType\Update::class)
            ->add($gpAsign);
            $app->delete('/{id}', \CM3_Lib\Action\Application\BadgeType\Delete::class)
            ->add($gpAsign);
        },
        '/Submission' => function (RouteCollectorProxy $app) use ($groupPerm) {
            $gpAsign =$groupPerm->withAllowedPerm(PermGroup::Submission_ReviewAssign());
            $app->get('', \CM3_Lib\Action\Application\Submission\Search::class)
            ->add($gpAsign->withAllowedPerms(array(
                PermGroup::Submission_View(),
                PermGroup::Submission_Edit()
            )));
            $app->post('', \CM3_Lib\Action\Application\Submission\Create::class)
            ->add($gpAsign->withAllowedPerm(PermGroup::Submission_Edit()));
            $app->get('/{id}', \CM3_Lib\Action\Application\Submission\Read::class)
            ->add($gpAsign->withAllowedPerm(PermGroup::Submission_View()));
            $app->post('/{id}', \CM3_Lib\Action\Application\Submission\Update::class)
            ->add($gpAsign->withAllowedPerm(PermGroup::Submission_Edit()));
            $app->delete('/{id}', \CM3_Lib\Action\Application\Submission\Delete::class)
            ->add($gpAsign->withAllowedPerm(PermGroup::Submission_Refund()));
        },
        '/Submission/{application_id}/Applicant' => function (RouteCollectorProxy $app) use ($groupPerm) {
            $gpAsign =$groupPerm->withAllowedPerm(PermGroup::Submission_ReviewAssign());
            $app->get('', \CM3_Lib\Action\Application\SubmissionApplicant\Search::class)
            ->add($gpAsign->withAllowedPerms(array(
                PermGroup::Submission_View(),
                PermGroup::Submission_Edit()
            )));
            $app->post('', \CM3_Lib\Action\Application\SubmissionApplicant\Create::class)
            ->add($gpAsign->withAllowedPerm(PermGroup::Submission_Edit()));
            $app->get('/{id}', \CM3_Lib\Action\Application\SubmissionApplicant\Read::class)
            ->add($gpAsign->withAllowedPerms(array(
                PermGroup::Submission_View(),
                PermGroup::Submission_Edit()
            )));
            $app->post('/{id}', \CM3_Lib\Action\Application\SubmissionApplicant\Update::class)
            ->add($gpAsign->withAllowedPerm(PermGroup::Submission_Edit()));
            $app->delete('/{id}', \CM3_Lib\Action\Application\SubmissionApplicant\Delete::class)
            ->add($gpAsign->withAllowedPerm(PermGroup::Submission_Edit()));
        },
        '/Submission/{application_id}/Assignment' => function (RouteCollectorProxy $app) use ($groupPerm) {
            $gpAsign = $groupPerm->withAllowedPerm(PermGroup::Submission_ReviewAssign());
            $app->get('', \CM3_Lib\Action\Application\Assignment\Search::class)
            ->add($gpAsign->withAllowedPerm(PermGroup::Submission_View()));
            $app->post('', \CM3_Lib\Action\Application\Assignment\Create::class)
            ->add($gpAsign);
            $app->get('/{id}', \CM3_Lib\Action\Application\Assignment\Read::class)
            ->add($gpAsign->withAllowedPerm(PermGroup::Submission_View()));
            $app->post('/{id}', \CM3_Lib\Action\Application\Assignment\Update::class)
            ->add($gpAsign);
            $app->delete('/{id}', \CM3_Lib\Action\Application\Assignment\Delete::class)
            ->add($gpAsign);
        },
    );

    $app->group(
        '/Application/{group_id}',
        function (RouteCollectorProxy $app) use ($r) {
            //Add all the sub-groups
            foreach ($r as $route => $definition) {
                $app->group($route, $definition);
            }
        }
    )->add($groupPerm->withAttributeName('group_id'));

    $app->get('/Application', \CM3_Lib\Action\Application\GroupList::class);
};
