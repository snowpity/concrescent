<?php

namespace CM3_Lib\Modules\Notification;

use CM3_Lib\database\SearchTerm;
use CM3_Lib\database\View;
use CM3_Lib\database\Join;

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
        private PHPMailer $PHPMailer,
        private MarkdownConverter $MarkdownConverter
    ) {
    }

    public function getMailerErrorInfo()
    {
        return $this->PHPMailer->ErrorInfo;
    }

    public function SendTemplateByName(string $to, string $templatename, array $entity)
    {
        $template = null;
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
            new SearchTerm('name', $templatename)
        ),
            limit: 1
        );
        if (count($templatedata) >0) {
            $template = $templatedata[0];
        } else {
            //TODO: Search the on-disk templates
        }
        if (is_null($template)) {
            throw new \Exception('Unable to load mail template "' . $templatename .'"');
        }

        return $this->SendTemplate($to, $template, $entity);
    }

    public function SendTemplateByBadge(string $to, string $reason, array $entity)
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
            //TODO: Search the on-disk templates
        }
    }


    public function SendTemplate(string $to, array $template, array $entity)
    {
        //Start prepping the message
        $this->PHPMailer->clearAllRecipients();
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
        $this->PHPMailer->addCustomHeader('Message-ID', $msgId);
        $this->PHPMailer->MessageID = $msgId;

        //Add addresses
        if (!empty($template['from'])) {
            $address = PHPMailer::parseAddresses($template['from'])[0];
            $this->PHPMailer->setFrom($address['address'], $address['name'], false);
        }
        if (!empty($template['reply_to'])) {
            foreach (PHPMailer::parseAddresses($template['reply_to']) as $address) {
                $this->PHPMailer->addReplyTo($address['address'], $address['name']);
            }
        }
        if (!empty($template['cc'])) {
            foreach (PHPMailer::parseAddresses($template['cc']) as $address) {
                $this->PHPMailer->addCC($address['address'], $address['name']);
            }
        }
        if (!empty($template['bcc'])) {
            foreach (PHPMailer::parseAddresses($template['bcc']) as $address) {
                $this->PHPMailer->addBCC($address['address'], $address['name']);
            }
        }

        //Set main recipient(s)
        foreach (PHPMailer::parseAddresses($to) as $address) {
            $this->PHPMailer->addAddress($address['address'], $address['name']);
        }


        //Do the marge-able fields next
        $this->PHPMailer->Subject = $this->merge_text($template['subject'], $entity);
        $body = $this->merge_text($template['body'], $entity);

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

        //get rid of binary data if present
        unset($entity['uuid_raw']);

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


    private function merge_text($text, $entity)
    {
        return preg_replace_callback(
            '/\\[\\[([^[]*)]]/', //Searches for stuff between [[words]]
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
