<?php

use Facebook\FacebookSession;
use Facebook\FacebookRequest;

define('APP_ID','803419359747395');
define('APP_SECRET', 'e57b40df28a833b5f175a237edd8b008');

class Task_Test extends Minion_Task {

	public function _execute() {
		FacebookSession::setDefaultApplication(APP_ID, APP_SECRET);
		
		$session = FacebookSession::newAppSession();
		var_dump($session->getToken());

		try {
			$session->validate();
		} catch (Facebook\FacebookRequestException $ex) {
			// Session not valid, Graph API returned an exception with the reason.
			echo $ex->getMessage();
		} catch (\Exception $ex) {
			// Graph API returned info, but it may mismatch the current app or have expired.
			echo $ex->getMessage();
		}
		
		$request = new FacebookRequest($session, 'GET', '/guss77');
		$response = $request->execute();
		$graphObject = $response->getGraphObject();
		var_dump($graphObject);
	}

}
