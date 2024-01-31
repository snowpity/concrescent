<?php

require_once __DIR__ .'/../../config/config.php';
require_once __DIR__ .'/../util/util.php';
require_once __DIR__ .'/database.php';

class cm_mail_db {

	public mixed $event_info;
	public cm_db $cm_db;

	public function __construct(cm_db $cm_db) {
		$this->event_info = $GLOBALS['cm_config']['event'];
		$this->cm_db = $cm_db;
		$this->cm_db->table_def('mail_templates', (
			'`name` VARCHAR(255) NOT NULL PRIMARY KEY,'.
			'`contact_address` VARCHAR(255) NOT NULL,'.
			'`from` VARCHAR(255) NOT NULL,'.
			'`bcc` VARCHAR(255) NULL,'.
			'`subject` VARCHAR(255) NOT NULL,'.
			'`type` ENUM(\'Text\',\'Simple HTML\',\'Full HTML\') NOT NULL,'.
			'`body` TEXT NOT NULL'
		));
		if ($this->cm_db->table_is_empty('mail_templates')) {
			$this->set_mail_template(array(
				'name' => 'attendee-paid',
				'contact-address' => 'registration@'.$_SERVER['SERVER_NAME'],
				'from' => 'registration@'.$_SERVER['SERVER_NAME'],
				'bcc' => 'registration@'.$_SERVER['SERVER_NAME'],
				'subject' => 'Your registration for [[event-name]]',
				'type' => 'Simple HTML',
				'body' => (
					"Greetings,\n\n".
					"Find below your magic link to review your badges for <b>[[event-name]]</b>. ".
					"<a href=\"[[review-link]]\">[[review-link]]</a>\n\n".
					"Thanks again,\n[[event-name]] Registration"
				)
			));
			$this->set_mail_template(array(
				'name' => 'attendee-retrieve',
				'contact-address' => 'registration@'.$_SERVER['SERVER_NAME'],
				'from' => 'registration@'.$_SERVER['SERVER_NAME'],
				'bcc' => 'registration@'.$_SERVER['SERVER_NAME'],
				'subject' => 'Your registration for [[event-name]]',
				'type' => 'Simple HTML',
				'body' => (
					"Greetings,\n\n".
					"Thank you for registering for <b>[[event-name]]</b>. ".
					"Your [[badge-type-name]] registration for <b>[[display-name]]</b> has been completed.\n\n".
					"Your badge will be available for pickup at the event. ".
					"Please bring a photo ID and a printout of this email message with you.\n\n".
					"<img src=\"[[qr-url]]\">\n\n".
					"You can review your order at any time at the following URL:\n\n".
					"<a href=\"[[review-link]]\">[[review-link]]</a>\n\n".
					"Thanks again,\n[[event-name]] Registration"
				)
			));
			$this->set_mail_template(array(
				'name' => 'staff-submitted',
				'contact-address' => 'hr@'.$_SERVER['SERVER_NAME'],
				'from' => 'hr@'.$_SERVER['SERVER_NAME'],
				'bcc' => 'hr@'.$_SERVER['SERVER_NAME'],
				'subject' => 'Your staff application for [[event-name]]',
				'type' => 'Simple HTML',
				'body' => (
					"Greetings,\n\n".
					"Thank you for applying to be a staffer for <b>[[event-name]]</b>.\n\n".
					"Your application has been received. ".
					"We will be contacting you soon regarding your application.\n\n".
					"If you have any questions, please contact ".
					"<b><a href=\"mailto:[[contact-address]]\">[[contact-address]]</a></b>.\n\n".
					"Thanks again,\n[[event-name]] Registration"
				)
			));
			$this->set_mail_template(array(
				'name' => 'staff-accepted',
				'contact-address' => 'hr@'.$_SERVER['SERVER_NAME'],
				'from' => 'hr@'.$_SERVER['SERVER_NAME'],
				'bcc' => 'hr@'.$_SERVER['SERVER_NAME'],
				'subject' => 'Your staff registration for [[event-name]]',
				'type' => 'Simple HTML',
				'body' => (
					"Greetings,\n\n".
					"Your staff application for <b>[[event-name]]</b> ".
					"in the position of <b>[[assigned-position-name-h]]</b> ".
					"has been approved! Welcome aboard!\n\n".
					"Your department head will be contacting your shortly. ".
					"Meanwhile, <b>please follow the following link</b> ".
					"to confirm your staff registration and, if required, ".
					"make a payment for your staff badge.\n\n".
					"<a href=\"[[review-link]]\">[[review-link]]</a>\n\n".
					"If you have any questions, please contact ".
					"<b><a href=\"mailto:[[contact-address]]\">[[contact-address]]</a></b>.\n\n".
					"Thanks again,\n[[event-name]] Registration"
				)
			));
			$this->set_mail_template(array(
				'name' => 'staff-paid',
				'contact-address' => 'hr@'.$_SERVER['SERVER_NAME'],
				'from' => 'hr@'.$_SERVER['SERVER_NAME'],
				'bcc' => 'hr@'.$_SERVER['SERVER_NAME'],
				'subject' => 'Your staff registration for [[event-name]]',
				'type' => 'Simple HTML',
				'body' => (
					"Greetings,\n\n".
					"Thank you for completing your staff registration for <b>[[event-name]]</b>.\n\n".
					"Your badge will be available for pickup at the event. ".
					"Please bring a photo ID and a printout of this email message with you.\n\n".
					"<img src=\"[[qr-url]]\">\n\n".
					"You can review your order at any time at the following URL:\n\n".
					"<a href=\"[[review-link]]\">[[review-link]]</a>\n\n".
					"Thanks again,\n[[event-name]] Registration"
				)
			));
			foreach ($GLOBALS['cm_config']['application_types'] as $context => $ctx_info) {
				$ctx_lc = strtolower($context);
				$ctx_name_lc = strtolower($ctx_info['nav_prefix']);
				$this->set_mail_template(array(
					'name' => 'application-submitted-'.$ctx_lc,
					'contact-address' => $ctx_name_lc.'@'.$_SERVER['SERVER_NAME'],
					'from' => $ctx_name_lc.'@'.$_SERVER['SERVER_NAME'],
					'bcc' => $ctx_name_lc.'@'.$_SERVER['SERVER_NAME'],
					'subject' => 'Your '.$ctx_name_lc.' application for [[event-name]]',
					'type' => 'Simple HTML',
					'body' => (
						"Greetings,\n\n".
						"Thank you for applying for <b>[[event-name]]</b>.\n\n".
						"Your application for <b>[[application-name]]</b> has been received.\n\n".
						"We will be contacting you soon regarding your application.\n\n".
						"If you have any questions, please contact ".
						"<b><a href=\"mailto:[[contact-address]]\">[[contact-address]]</a></b>.\n\n".
						"Thanks again,\n[[event-name]] Registration"
					)
				));
				$this->set_mail_template(array(
					'name' => 'application-accepted-'.$ctx_lc,
					'contact-address' => $ctx_name_lc.'@'.$_SERVER['SERVER_NAME'],
					'from' => $ctx_name_lc.'@'.$_SERVER['SERVER_NAME'],
					'bcc' => $ctx_name_lc.'@'.$_SERVER['SERVER_NAME'],
					'subject' => 'Your '.$ctx_name_lc.' registration for [[event-name]]',
					'type' => 'Simple HTML',
					'body' => (
						"Congratulations!\n\n".
						"Your application for <b>[[application-name]]</b> ".
						"at <b>[[event-name]]</b> has been approved!\n\n".
						"<b>Please follow the following link</b> to confirm your registration ".
						"and, if required, make a payment for your badges.\n\n".
						"<a href=\"[[review-link]]\">[[review-link]]</a>\n\n".
						"If you have any questions, please contact ".
						"<b><a href=\"mailto:[[contact-address]]\">[[contact-address]]</a></b>.\n\n".
						"Thanks again,\n[[event-name]] Registration"
					)
				));
				$this->set_mail_template(array(
					'name' => 'application-paid-'.$ctx_lc,
					'contact-address' => $ctx_name_lc.'@'.$_SERVER['SERVER_NAME'],
					'from' => $ctx_name_lc.'@'.$_SERVER['SERVER_NAME'],
					'bcc' => $ctx_name_lc.'@'.$_SERVER['SERVER_NAME'],
					'subject' => 'Your '.$ctx_name_lc.' registration for [[event-name]]',
					'type' => 'Simple HTML',
					'body' => (
						"Greetings,\n\n".
						"Your registration for <b>[[application-name]]</b> ".
						"at <b>[[event-name]]</b> has been completed. Thank you.\n\n".
						"Your badge will be available for pickup at the event. ".
						"Please bring a photo ID and a printout of this email message with you.\n\n".
						"<img src=\"[[qr-url]]\">\n\n".
						"You can review your order at any time at the following URL:\n\n".
						"<a href=\"[[review-link]]\">[[review-link]]</a>\n\n".
						"Thanks again,\n[[event-name]] Registration"
					)
				));
				$this->set_mail_template(array(
					'name' => 'application-waitlisted-'.$ctx_lc,
					'contact-address' => $ctx_name_lc.'@'.$_SERVER['SERVER_NAME'],
					'from' => $ctx_name_lc.'@'.$_SERVER['SERVER_NAME'],
					'bcc' => $ctx_name_lc.'@'.$_SERVER['SERVER_NAME'],
					'subject' => 'Your '.$ctx_name_lc.' application for [[event-name]]',
					'type' => 'Simple HTML',
					'body' => (
						"Greetings,\n\n".
						"Thank you for applying for <b>[[event-name]]</b>.\n\n".
						"Due to the number of applications we have received, your application ".
						"for <b>[[application-name]]</b> has been placed on our waitlist. ".
						"You are near the top, so you still have a chance to get in! ".
						"We will let you know as soon as a spot becomes available.\n\n".
						"Thank you for applying and thank you for your patience.\n\n".
						"If you have any questions, please contact ".
						"<b><a href=\"mailto:[[contact-address]]\">[[contact-address]]</a></b>.\n\n".
						"Thanks again,\n[[event-name]] Registration"
					)
				));
				$this->set_mail_template(array(
					'name' => 'application-rejected-'.$ctx_lc,
					'contact-address' => $ctx_name_lc.'@'.$_SERVER['SERVER_NAME'],
					'from' => $ctx_name_lc.'@'.$_SERVER['SERVER_NAME'],
					'bcc' => $ctx_name_lc.'@'.$_SERVER['SERVER_NAME'],
					'subject' => 'Your '.$ctx_name_lc.' application for [[event-name]]',
					'type' => 'Simple HTML',
					'body' => (
						"Greetings,\n\n".
						"Thank you for applying for <b>[[event-name]]</b>.\n\n".
						"Due to the number of applications we have received, your application ".
						"for <b>[[application-name]]</b> has been placed on our waitlist. ".
						"If any spots become available, we will let you know.\n\n".
						"Thank you for applying and thank you for your patience.\n\n".
						"If you have any questions, please contact ".
						"<b><a href=\"mailto:[[contact-address]]\">[[contact-address]]</a></b>.\n\n".
						"Thanks again,\n[[event-name]] Registration"
					)
				));
			}
		}
	}

	public function get_contact_address($name) {
		if (!$name) return false;
		$stmt = $this->cm_db->connection->prepare(
			'SELECT `contact_address`'.
			' FROM `mail_templates`' .
			' WHERE `name` = ? LIMIT 1'
		);
		$stmt->bind_param('s', $name);
		$stmt->execute();
		$stmt->bind_result($contact_address);
		$result = $stmt->fetch() ? $contact_address : false;
		$stmt->close();
		return $result;
	}

	public function get_mail_template($name) {
		if (!$name) return false;
		$stmt = $this->cm_db->connection->prepare(
			'SELECT `name`, `contact_address`, `from`, `bcc`, `subject`, `type`, `body`'.
			' FROM `mail_templates`' .
			' WHERE `name` = ? LIMIT 1'
		);
		$stmt->bind_param('s', $name);
		$stmt->execute();
		$stmt->bind_result($name, $contact_address, $from, $bcc, $subject, $type, $body);
		if ($stmt->fetch()) {
			$result = array(
				'name' => $name,
				'contact-address' => $contact_address,
				'from' => $from,
				'bcc' => $bcc,
				'subject' => $subject,
				'type' => $type,
				'body' => $body,
				'search-content' => array($name, $contact_address, $from, $bcc, $subject)
			);
			$stmt->close();
			return $result;
		}
		$stmt->close();
		return false;
	}

	public function list_mail_templates() {
		$templates = array();
		$stmt = $this->cm_db->connection->prepare(
			'SELECT `name`, `contact_address`, `from`, `bcc`, `subject`, `type`, `body`'.
			' FROM `mail_templates`' .
			' ORDER BY `name`'
		);
		$stmt->execute();
		$stmt->bind_result($name, $contact_address, $from, $bcc, $subject, $type, $body);
		while ($stmt->fetch()) {
			$templates[] = array(
				'name' => $name,
				'contact-address' => $contact_address,
				'from' => $from,
				'bcc' => $bcc,
				'subject' => $subject,
				'type' => $type,
				'body' => $body,
				'search-content' => array($name, $contact_address, $from, $bcc, $subject)
			);
		}
		$stmt->close();
		return $templates;
	}

	public function set_mail_template($template) {
		if (!$template || !isset($template['name']) || !$template['name']) return false;
		$stmt = $this->cm_db->connection->prepare(
			'INSERT INTO `mail_templates` SET '.
			'`name` = ?, `contact_address` = ?, `from` = ?, `bcc` = ?, `subject` = ?, `type` = ?, `body` = ?'.
			' ON DUPLICATE KEY UPDATE '.
			'`name` = ?, `contact_address` = ?, `from` = ?, `bcc` = ?, `subject` = ?, `type` = ?, `body` = ?'
		);
		$stmt->bind_param(
			'ssssssssssssss',
			$template['name'], $template['contact-address'], $template['from'], $template['bcc'], $template['subject'], $template['type'], $template['body'],
			$template['name'], $template['contact-address'], $template['from'], $template['bcc'], $template['subject'], $template['type'], $template['body']
		);
		$success = $stmt->execute();
		$stmt->close();
		return $success;
	}

	public function clear_mail_template($name) {
		if (!$name) return false;
		$stmt = $this->cm_db->connection->prepare(
			'DELETE FROM `mail_templates`' .
			' WHERE `name` = ? LIMIT 1'
		);
		$stmt->bind_param('s', $name);
		$success = $stmt->execute();
		$stmt->close();
		return $success;
	}

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
