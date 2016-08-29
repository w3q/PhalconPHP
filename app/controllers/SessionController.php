<?php

use Phalcon\Mvc\Controller;

class SessionController extends Controller
{
    // ...

    private function _registerSession($user)
    {
        $this->session->set(
            'auth',
            [
                'id'   => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'isAdmin' => $user->isAdmin
            ]
        );
    }

    public function indexAction()
    {
    }

    /**
     * This action authenticate and logs a user into the application
     */
    public function startAction()
    {
        if ($this->request->isPost()) {

            // Get the data from the user
            $email    = $this->request->getPost('email');
            $password = $this->request->getPost('password');

            // Find the user in the database
            $user = Users::findFirst(
                [
                    "(email = :email: OR name = :email:) AND password = :password:",
                    'bind' => [
                        'email'    => $email,
                        'password' => $password
                    ]
                ]
            );

            if ($user != false) {

                $this->_registerSession($user);

                // Forward to the 'invoices' controller if the user is valid
                return $this->response->redirect('/user');
            }

            $this->flashSession->error('Wrong email/password');
        }

        // Forward to the login form again
        return $this->dispatcher->forward(
            [
                'controller' => 'session',
                'action'     => 'index'
            ]
        );
    }

    function logoutAction () {
        $this->session->set('auth', NULL);

        $this->flashSession->success("You were sucessfully logged out.");
        return $this->response->redirect('/session');
    }
}