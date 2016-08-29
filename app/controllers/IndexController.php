<?php

use Phalcon\Mvc\Controller;

class IndexController extends Controller
{

	public function indexAction()
	{
		$auth = $this->session->get('auth');

		$this->view->auth = $auth;
	}
}
