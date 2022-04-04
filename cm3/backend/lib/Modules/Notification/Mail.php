<?php

namespace CM3_Lib\Modules\Notification;

use CM3_Lib\database\SearchTerm;
use CM3_Lib\database\View;
use CM3_Lib\database\Join;

use CM3_Lib\models\eventinfo;
use CM3_Lib\models\mail\log;
use CM3_Lib\models\mail\template;
use CM3_Lib\models\mail\templatemap;

use CM3_Lib\util\CurrentUserInfo;

use PHPMailer\PHPMailer\PHPMailer;
use League\CommonMark\MarkdownConverter;

class Mail
{
    public function __construct(
        private CurrentUserInfo $CurrentUserInfo,
        private log $log,
        private template $template,
        private templatemap $templatemap,
        private eventinfo $eventinfo,
        private PHPMailer $PHPMailer,
        private MarkdownConverter $MarkdownConverter
    ) {
    }

    public function getMailerErrorInfo()
    {
        return $this->PHPMailer->ErrorInfo;
    }

    public function SendTemplate(string $to, string|array|int $template, array $entity, ?string $cc = null)
    {
        //Start prepping the message
        $template = $this->GetTemplate($template);
        $this->PrepareMessage($template, $entity);

        //Set main recipient(s)
        $this->addAddress('Address', $to);

        //Set CCs
        if (!empty($cc)) {
            $this->addAddress('CC', $cc);
        }

        //Remove raw stuff
        unset($entity['uuid_raw']);
        unset($entity['qr_data_uri']);

        //Send it
        if ($this->PHPMailer->send()) {
            //Log the success
            $this->log->Create(array(
                'template_id' => $template['id'],
                'success'=>1,
                'data'=>json_encode($entity),
                'result'=>'sent'
            ));
            return true;
        } else {
            //Log the failure
            $this->log->Create(array(
                'template_id' => $template['id'],
                'success'=>0,
                'data'=>json_encode($entity),
                'result'=>'Failed:'.$this->PHPMailer->ErrorInfo
            ));
            return false;
        }
    }

    public function RenderTemplate(string|array|int $template, array $entity)
    {
        //Start prepping the message
        $template = $this->GetTemplate($template);
        $this->PrepareMessage($this->GetTemplate($template), $entity);

        //Prepare to send it (but don't actually do so)
        $this->PHPMailer->preSend();
        //Get what we would have sent
        return $this->GetLastMessage();
    }

    public function GetTemplate(string|array|int $template)
    {
        if (is_string($template) || is_int($template)) {
            $templatedata = $this->template->Search(
                array(
                'id',
                'name',
                'reply_to',
                'from',
                'cc',
                'bcc',
                'subject',
                'format',
                'body',
                'attachments'
                ),
                array(
                $this->CurrentUserInfo->EventIdSearchTerm(),
                new SearchTerm('active', 1),
                new SearchTerm('', '', subSearch:array(
                    new SearchTerm('name', $template),
                    new SearchTerm('id', $template, 'OR')
                ))

            ),
                limit: 1
            );
            if (count($templatedata) >0) {
                $template = $templatedata[0];
            } else {
                //Search the on-disk templates
                $templatefile = __DIR__ . '/../../../config/templates/Mail/' . $template . '.json';
                if (file_exists($templatefile)) {
                    //Load it up!
                    $template = json_decode(file_get_contents($templatefile), true);
                    //Make sure it knows its name
                    $template['name'] = basename($templatefile, '.json');
                    $template['id'] = 0;
                }
                if (is_null($template) || is_string($template)) {
                    throw new \Exception('Unable to load mail template "' . $templatename .'"');
                }
            }
        }
        return $template;
    }


    public function GetTemplateByBadge(string $reason, array $entity)
    {
        $template = null;
        $templatedata = $this->template->Search(
            new View(
                array(
                    'id',
                    'name',
                    'reply_to',
                    'from',
                    'cc',
                    'bcc',
                    'subject',
                    'format',
                    'body',
                    'attachments'
                ),
                array(
                        new Join(
                            $this->templatemap,
                            array(
                                'id'=>'template_id',
                                new SearchTerm('context', $entity['context'] ?? 'A'),
                                new SearchTerm('badge_type_id', $entity['badge_type_id']??0),
                                new SearchTerm('reason', $reason),
                            )
                        )
                )
            ),
            array(
                $this->CurrentUserInfo->EventIdSearchTerm(),
                new SearchTerm('active', 1),
                new SearchTerm('name', $templatename)
            ),
            limit: 1
        );
        if (count($templatedata) >0) {
            $template = $templatedata[0];
        } else {
            //Search the on-disk templates
            $templatefile = __DIR__ . '/../../../config/templates/Mail/' . ($entity['context'] ??'A') . '-' . $reason . '.json';
            if (file_exists($templatefile)) {
                //Load it up!
                $template = json_decode(file_get_contents($templatefile), true);
                //Make sure it knows its name
                $template['name'] = basename($templatefile, '.json');
                $template['id'] = 0;
            }
            if (is_null($template) || is_string($template)) {
                throw new \Exception('Unable to load mail template "' . $templatename .'"');
            }
        }
    }

    public function GetLastMessage(bool $includeAttachements = false)
    {
        $result = array(
            'to'       => $this->PHPMailer->getToAddresses(),
            'cc'       => $this->PHPMailer->getCcAddresses(),
            'bcc'      => $this->PHPMailer->getBccAddresses(),
            'cc'       => $this->PHPMailer->getCcAddresses(),
            'reply_to' => $this->PHPMailer->getReplyToAddresses(),
            'subject'  => $this->PHPMailer->Subject,
            'body'     => $this->PHPMailer->Body,

        );
        if ($includeAttachements) {
            $result['attachments'] = $this->PHPMailer->getAttachments();
            //Base64 encode the attachements
            foreach ($result['attachments'] as $key => &$value) {
                $value[0] = base64_encode($value[0]);
            }
        }

        return $result;
    }
    private function PrepareMessage(array $template, array $entity)
    {
        $this->PHPMailer->clearAllRecipients();
        $this->PHPMailer->clearReplyTos();
        $this->PHPMailer->Body = '';
        $this->PHPMailer->AltBody = '';
        $this->PHPMailer->clearAttachments();
        $this->PHPMailer->clearCustomHeaders();

        //Add some headers
        $this->PHPMailer->XMailer = 'CONcrescent/3.0 PHP/' . phpversion() .' PHPMailer' . $this->PHPMailer::VERSION ;
        //Generate the message ID
        $msgId = '<ei' . $this->CurrentUserInfo->GetEventId() . '-';
        if (isset($template['name'])) {
            $msgId .= ('tn'.$template['name'] .'-');
        }
        $msgId .= 'ci'.$this->CurrentUserInfo->GetContactId() . '-';
        if (isset($entity['badge_type_id'])) {
            $msgId .= 'bt'.$entity['badge_type_id'] .'-';
        }
        if (isset($entity['context'])) {
            $msgId .= 'cx'.$entity['context'] .'-';
        }
        if (isset($entity['id'])) {
            $msgId .= 'id'.$entity['id'] .'-';
        }
        $msgId .= md5(serialize($entity));
        //TODO: Is there a better way to do this?
        $msgId .= '@' . strtolower($_SERVER['SERVER_NAME']) .'>';
        //$this->PHPMailer->addCustomHeader('Message-ID', $msgId);
        $this->PHPMailer->MessageID = $msgId;

        //Add addresses
        if (!empty($template['from'])) {
            $address = PHPMailer::parseAddresses($template['from'])[0];
            $this->PHPMailer->setFrom($address['address'], $address['name'], false);
        }
        if (!empty($template['reply_to'])) {
            $this->addAddress('ReplyTo', $template['reply_to']);
        }
        if (!empty($template['cc'])) {
            $this->addAddress('CC', $template['cc']);
        }
        if (!empty($template['bcc'])) {
            $this->addAddress('BCC', $template['bcc']);
        }

        //Merge in some global merge fields
        $mergeFields = $this->wrap_entity($entity);

        //Do the marge-able fields next
        $this->PHPMailer->Subject = $this->merge_text($template['subject'], $mergeFields);
        $body = $this->merge_text($template['body'], $mergeFields);

        switch ($template['format']) {
            case 'Text Only':
                $this->PHPMailer->Body = $body;
                break;
            case 'Markdown':
                //parse into HTML
                $this->PHPMailer->msgHTML($this->MarkdownConverter->convert($body));
                break;
            case 'Full HTML':
                $this->PHPMailer->msgHTML($body);
                break;
        }
    }
    private function addAddress(string $type, $addressLine)
    {
        foreach (PHPMailer::parseAddresses($addressLine) as $address) {
            $this->PHPMailer->{'add' . $type}($address['address'], $address['name']);
        }
    }
    private function wrap_entity($entity)
    {
        $result = array_merge(
            $entity,
            array(
                'event'=>$this->eventinfo->GetByID($this->CurrentUserInfo->GetEventId(), array())
            )
        );

        //Clear out raw stuff
        return array_diff_key($result, array_flip(array(
            'uuid_raw'
        )));
    }

    private function merge_text($text, $entity)
    {
        return preg_replace_callback(
            '/\\[\\[([^[\]]*)]]/', //Searches for stuff between [[words]]
            function ($matches) use ($entity) {
                return $this->getValueByPath($entity, trim($matches[1]));
            },
            $text
        );
    }

    //Shamelessly stolen from SO https://stackoverflow.com/a/27930060 with mods
    /**
    * Gets the value from input based on path.
    * Handles objects, arrays and scalars. Nesting can be mixed.
    * E.g.: $input->a->b->c = 'val' or $input['a']['b']['c'] = 'val' will
    * return "val" with path "a[b][c]".
    * @param mixed $input
    * @param string $path
    * @param mixed $default Optional default value to return on failure (null)
    * @return NULL|mixed NULL on failure, or the value on success (which may also be NULL)
    */
    private function getValueByPath($input, $path, $default='')
    {
        if (!(isset($input) && ($this->isIterable($input) || is_scalar($input)))) {
            return $default; // null already or we can't deal with this, return early
        }
        $pathArray = explode('.', $path);
        $last = &$input;
        foreach ($pathArray as $key) {
            if (is_object($last) && property_exists($last, $key)) {
                $last = &$last->$key;
            } elseif ((is_scalar($last) || is_array($last)) && isset($last[$key])) {
                $last = &$last[$key];
            } else {
                return $default;
            }
        }
        return $last;
    }

    /**
     * Check if a value/object/something is iterable/traversable,
     * e.g. can it be run through a foreach?
     * Tests for a scalar array (is_array), an instance of Traversable, and
     * and instance of stdClass
     * @param mixed $value
     * @return boolean
     */
    private function isIterable($value)
    {
        return is_array($value) || $value instanceof Traversable || $value instanceof stdClass;
    }


    //End code theft
}
