<?php

require_once __DIR__ .'/../../config/config.php';

class cm_slack {

	public $hook_urls;

	public function __construct() {
		$config = $GLOBALS['cm_config']['slack'];
		$this->hook_urls = $config['hook_url'];
	}

	public function get_hook_url($path) {
		$hook_urls = $this->hook_urls;
		if (!is_array($path)) $path = array($path);
		foreach ($path as $name) {
			if (!is_array($hook_urls)) return false;
			if (!isset($hook_urls[$name])) return false;
			$hook_urls = $hook_urls[$name];
		}
		if (is_array($hook_urls)) return false;
		return $hook_urls;
	}

	public function post_message($path, $text) {
		$url = $this->get_hook_url($path);
		if (!$url || !$text) return;
		$payload = urlencode(json_encode(array('text' => $text)));
		$curl = curl_init($url);
		curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_POSTFIELDS, 'payload=' . $payload);
		curl_exec($curl);
		curl_close($curl);
	}

	public function make_emoji($name) {
		return (':'.$name.':');
	}

	public function make_link($href, $text) {
		return $text ? ('<'.$href.'|'.$text.'>') : ('<'.$href.'>');
	}

}
