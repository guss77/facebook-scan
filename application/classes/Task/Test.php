<?php

/**
 * Get Facebook data for a user name
 *  It can accept the following options:
 *  - name: required - the user name to look up
 */
class Task_Test extends Minion_Task {

	protected $_options = [
		'name' => NULL
	];
	
	private function get_url($url) {
		$options  = [
			'http' => [ 'user_agent' => 'Mozilla/5.0 (X11; Linux i586; rv:31.0) Gecko/20100101 Firefox/31.0' ]
		];
		$context  = stream_context_create($options);
		try {
			$out = file_get_contents($url, false, $context);
			/*
			list($proto, $code, $text) = explode(" ", $http_response_header[0],3);
			if ((int)$code >= 300)
				return null;
			*/
			return $out;
		} catch (ErrorException $e) {
			return null; // running with E_ALL, file_get_contents reports HTTP errors by throwing
		}
	}
	
	private function get_hidden_blocks($content) {
		preg_match_all("'hidden_elem[^>]+>[^<]*<!--(.*?)-->'is", $content, $matches, PREG_SET_ORDER) or die("Error reading facebook\n");
		$out = [];
		foreach ($matches as $match)
			$out[] = $match[1];
		return $out;
	}
	
	private function get_node($html, $selector) {
		$dom = new PHPHtmlParser\Dom;
		$dom->load($html);
		$content = $dom->find($selector);
		return count($content) ? $content[0] : false;
	}

	private function get_all_nodes($html, $selector) {
		$dom = new PHPHtmlParser\Dom;
		$dom->load($html);
		$content = $dom->find($selector);
		return count($content) ? $content : false;
	}
	
	private function instant_search($name) {
		$name = urlencode(preg_replace("/\W+/", '-', $name));
		$fbpage = $this->get_url("https://www.facebook.com/public/$name");
		foreach ($this->get_hidden_blocks($fbpage) as $block) {
			if ($found = $this->get_all_nodes($block, '.instant_search_title')) {
				foreach ($found as $node) {
					echo basename(parse_url($node->find('a')[0]->href, PHP_URL_PATH));
					echo "\n";
				}
			}
		}
	}
	
	private function get_user_data($name) {
		$name = urlencode($name);
		$fbpage = $this->get_url("https://www.facebook.com/$name");
		if (is_null($fbpage))
			return null;
		
		$out = [];
		foreach ($this->get_hidden_blocks($fbpage) as $block) {
			if ($found = $this->get_node($block, '.profilePic')) {
				$out['Real name'] = $found->alt;
				$out['Pictures'] = html_entity_decode($found->src);
			}
			
			if ($found = $this->get_node($block, '.uiLinkDark')) {
				$out['Link'] = html_entity_decode($found->href);
			}
		}
		return $out;
	}
	
	private function get_friends($name) {
		$name = urlencode($name);
		$fbpage = $this->get_url("https://www.facebook.com/$name/friends");
		if (is_null($fbpage))
			return null;
		
		$out = [];
		foreach ($this->get_hidden_blocks($fbpage) as $block) {
			if ($found = $this->get_all_nodes($block, '.profileFriendsText')) {
				foreach ($found as $friend)
					$out[] = $friend->find('a')[0]->href;
			}
		}
		return $out;
	}

	protected function _execute() {
		$opts = func_get_arg(0);
		if (!$opts['name']) {
			$this->_help();
			return;
		}
		
		
		$user = $this->get_user_data($opts['name']);
		if (!$user) {
			echo "User not found, running instant search...\n";
			$this->instant_search($opts['name']);
			return;
		}

		foreach ($user as $attr => $val)
			echo "$attr: $val\n";
			
		$friends = $this->get_friends($opts['name']);
		if (!$friends)
			die("Error getting friends!\n");
		echo "Friends:\n";
		foreach ($friends as $friend) {
			echo "\t$friend\n";
		}
	}

}
