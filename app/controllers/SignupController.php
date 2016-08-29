<?php

use Phalcon\Mvc\Controller;

class SignupController extends Controller
{

	public function indexAction()
	{

	}

	public function registerAction()
	{

		$user = new Users();

		// Store and check for errors
		$success = $user->save(
			$this->request->getPost(),
			array('name', 'email', 'password')
		);

		if ($success) {
			$this->flashSession->success("Registration was successful, now you can log in!");
			return $this->response->redirect('/session');
		} else {
			$this->flashSession->error("Sorry, the following problems were generated: ");
			foreach ($user->getMessages() as $message) {
				$this->flashSession->error($message->getMessage());
			}
			return $this->response->redirect('/signup');
		}

		$this->view->disable();
	}

}
