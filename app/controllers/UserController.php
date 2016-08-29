<?php

use Phalcon\Mvc\Controller;

class UserController extends Controller
{

    public function indexAction()
    {
        $auth = $this->session->get('auth');
        $this->view->user = $auth;
    }

    public function listAction()
    {
        $users = Users::find();

        $this->view->users = $users;
    }

}
