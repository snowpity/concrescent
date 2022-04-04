<?php

namespace CM3_Lib\Action\Public;

use CM3_Lib\database\SelectColumn;
use CM3_Lib\database\SearchTerm;
use CM3_Lib\database\View;
use CM3_Lib\database\Join;
use CM3_Lib\models\forms\question;
use CM3_Lib\models\forms\questionmap;
use CM3_Lib\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Action.
 */
final class ListAllQuestions
{
    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param eventinfo $eventinfo The service
     */
    public function __construct(private Responder $responder, private question $question, private questionmap $questionmap)
    {
    }

    /**
     * Action.
     *
     * @param ServerRequestInterface $request The request
     * @param ResponseInterface $response The response
     *
     * @return ResponseInterface The response
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, $params): ResponseInterface
    {
        $viewData = new View(
            array(
              'id',
              'order',
              'title',
              'text',
              'type',
              'values',
              'visible_condition',
              new SelectColumn('required', JoinedTableAlias: 'q'),
              new SelectColumn('badge_type_id', JoinedTableAlias: 'q')
          ),
            array(
            new Join(
                $this->questionmap,
                array(
                  'question_id'=>'id',
                ),
                'INNER',
                'q',
                array('question_id','required','badge_type_id'),
                array(
                 new SearchTerm('context_code', $params['context_code'])
               )
            )
          )
        );

        $whereParts = array(
          new SearchTerm('event_id', $params['event_id']),
          new SearchTerm('active', 1)
        );

        $order = array('order' => false);


        // Invoke the Domain with inputs and retain the result
        $data = $this->question->Search($viewData, $whereParts, $order);

        //Post-process
        //Bring out the badge_type_id as the key to the data
        $newdata = array_fill_keys(array_unique(array_column($data, 'badge_type_id')), array());
        $removekeys = array_flip(array('badge_type_id'));
        array_walk($data, function (&$entry) use (&$newdata, $removekeys) {
            $entry['values'] = array_map(function ($value) {
                return trim($value);
            }, explode("\n", $entry['values']));
            //Add it in
            $newdata[$entry['badge_type_id']][] = array_diff_key($entry, $removekeys);
        });

        // Build the HTTP response
        return $this->responder
            ->withJson($response, $newdata);
    }
}
