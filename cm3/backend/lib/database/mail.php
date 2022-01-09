<?php

require_once dirname(__FILE__).'/../../config/config.php';
require_once dirname(__FILE__).'/../util/util.php';
require_once dirname(__FILE__).'/database.php';


class cm_mail_templates_db extends cm_Table {
	protected function setupTableDefinitions() : void
	{
		$this->TableName = 'Mail_Templates';
		$this->ColumnDefs = array(
			'id' 			=> new cm_Column('BIGINT', null, false, true, false, true, null),
			'event_id'		=> new cm_Column('INT', null, false, false, false, true),
			'name'			=> new cm_Column('VARCHAR', 255, false),
			'active'        => new cm_Column('BOOLEAN', null, false, defaultValue: 'false'),
			'reply_to'		=> new cm_Column('VARCHAR', 300, false),
			'from'			=> new cm_Column('VARCHAR', 300, false),
			'bcc'			=> new cm_Column('VARCHAR', 2000, true),
			'subject'		=> new cm_Column('VARCHAR', 1000, false),
			'format'		=> new cm_Column('ENUM',
				array(
					'Text Only',
					'Markdown',
					'Full HTML' //Is there a reason?
				), false),
			'body'			=> new cm_Column('TEXT',null,true)
		);
		$this->IndexDefs = array();
		$this->PrimaryKeys = array('id'=>false);
		$this->DefaultSearchColumns = array('id');
	}

	//TODO: Fix please
	public function send_mail($to, $template, $entity) {
		if ($to && $template && isset($template['body']) && trim($template['body']) && $entity) {
			$mail_fields = array();
			foreach ($entity as $k => $v) {
				$mail_fields[strtolower(str_replace('_', '-', $k))] = $v;
				$mail_fields[strtolower(str_replace('-', '_', $k))] = $v;
			}
			foreach ($this->event_info as $k => $v) {
				$mail_fields['event-' . strtolower(str_replace('_', '-', $k))] = $v;
				$mail_fields['event_' . strtolower(str_replace('-', '_', $k))] = $v;
			}
			$mail_fields['contact-address'] = $template['contact-address'];
			$mail_fields['contact_address'] = $template['contact-address'];

			$mail_subject = mail_merge($template['subject'], $mail_fields);
			$mail_subject = str_replace("\r\n", " ", $mail_subject);
			$mail_subject = str_replace("\r", " ", $mail_subject);
			$mail_subject = str_replace("\n", " ", $mail_subject);

			switch ($template['type']) {
				case 'Full HTML':
					$content_type = 'text/html; charset=UTF-8';
					$mail_body = mail_merge_html($template['body'], $mail_fields);
					break;
				case 'Simple HTML':
					$content_type = 'text/html; charset=UTF-8';
					$mail_body = '<html><body>' . mail_merge_html(safe_html_string($template['body']), $mail_fields) . '</body></html>';
					break;
				default:
					$content_type = 'text/plain; charset=UTF-8';
					$mail_body = mail_merge($template['body'], $mail_fields);
					break;
			}

			if (isset($entity['qr-data']) && $entity['qr-data']) {
				$mail_reference = strtolower(preg_replace('/[^A-Za-z0-9]+/', '-', $entity['qr-data']));
			} else if (isset($entity['uuid']) && $entity['uuid']) {
				$mail_reference = 'cm-uuid-' . strtolower(preg_replace('/[^A-Za-z0-9]+/', '-', $entity['uuid']));
			} else if (isset($entity['id-string']) && $entity['id-string']) {
				$mail_reference = 'cm-idstr-' . strtolower(preg_replace('/[^A-Za-z0-9]+/', '-', $entity['id-string']));
			} else {
				$mail_reference = 'cm-hash-' . strtolower(preg_replace('/[^A-Za-z0-9]+/', '-', md5(serialize($entity))));
			}
			$mail_reference .= '@' . strtolower($_SERVER['SERVER_NAME']);

			$mail_headers = array();
			if ($template['from']) $mail_headers[] = 'From: ' . $template['from'];
			if ($template['bcc']) $mail_headers[] = 'Bcc: ' . $template['bcc'];
			$mail_headers[] = 'References: <' . $mail_reference . '>';
			$mail_headers[] = 'X-Mailer: CONcrescent/2.0 PHP/' . phpversion();
			$mail_headers[] = 'MIME-Version: 1.0';
			$mail_headers[] = 'Content-Type: ' . $content_type;
			$mail_headers = implode("\r\n", $mail_headers);

			return mail($to, $mail_subject, $mail_body, $mail_headers);
		}
	}
}

cm_mail_log_db extends cm_Table {
	protected function setupTableDefinitions() : void
	{
		$this->TableName = 'Mail_Log';
		$this->ColumnDefs = array(
			'id' 			=> new cm_Column('BIGINT', null, false, true, false, true, null),
			'template_id'		=> new cm_Column('INT', null, false, false, false, true),
			'success'        => new cm_Column('BOOLEAN', null, false, defaultValue: 'false'),
			'meta'			=> new cm_Column('VARCHAR', 255, true),
			'data'			=> new cm_Column('TEXT',null,true),
			'result'			=> new cm_Column('VARCHAR', 255, false)
		);
		$this->IndexDefs = array();
		$this->PrimaryKeys = array('id'=>false);
		$this->DefaultSearchColumns = array('template_id','meta','result');
	}
}
