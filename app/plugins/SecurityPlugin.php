<?php

use Phalcon\Events\Event;
use Phalcon\Mvc\User\Plugin;
use Phalcon\Mvc\Dispatcher;
use Phalcon\Acl\Adapter\Memory as AclList;
use Phalcon\Acl;
use Phalcon\Acl\Role;
use Phalcon\Acl\Resource;

class SecurityPlugin extends Plugin
{
    /**
     * Returns an existing or new access control list
     *
     * @returns AclList
     */
    public function getAcl()
    {
        if (!isset($this->persistent->acl)) {
            $acl = new AclList();
            $acl->setDefaultAction(Acl::DENY);
            // Register roles
            $roles = [
                'admins' => new Role(
                    'Admins',
                    'Super admin, have access to everything, needs to log in and have flag in DB'
                ),
                'users'  => new Role(
                    'Users',
                    'Member privileges, granted after sign in.'
                ),
                'guests' => new Role(
                    'Guests',
                    'Anyone browsing the site who is not signed in is considered to be a "Guest".'
                )
            ];
            foreach ($roles as $role) {
                $acl->addRole($role);
            }

            //Admin private resources
            $adminResources = array(
                'user' => array('list')
            );

            foreach ($adminResources as $resource => $actions) {
                $acl->addResource(new Resource($resource), $actions);
            }

            //Private area resources
            $privateResources = array(
                'user'    => array('index'),
                'session'     => array('logout')
            );
            foreach ($privateResources as $resource => $actions) {
                $acl->addResource(new Resource($resource), $actions);
            }
            //Public area resources
            $publicResources = array(
                'index'      => array('index'),
                'session'      => array('index', 'start'),
                'signup'   => array('index', 'register')
            );
            foreach ($publicResources as $resource => $actions) {
                $acl->addResource(new Resource($resource), $actions);
            }
            //Grant access to public areas to both users and guests
            foreach ($roles as $role) {
                foreach ($publicResources as $resource => $actions) {
                    foreach ($actions as $action){
                        $acl->allow($role->getName(), $resource, $action);
                    }
                }
            }
            //Grant access to private area to role Users
            foreach ($privateResources as $resource => $actions) {
                foreach ($actions as $action){
                    $acl->allow('Users', $resource, $action);
                }
            }

            //Grant access to private user area for admin
            foreach ($privateResources as $resource => $actions) {
                foreach ($actions as $action){
                    $acl->allow('Admins', $resource, $action);
                }
            }

            //Grant access to private admin area for admin
            foreach ($adminResources as $resource => $actions) {
                foreach ($actions as $action){
                    $acl->allow('Admins', $resource, $action);
                }
            }

            //The acl is stored in session, APC would be useful here too
            $this->persistent->acl = $acl;
        }
        return $this->persistent->acl;
    }

    public function beforeExecuteRoute(Event $event, Dispatcher $dispatcher)
    {
        // Check whether the "auth" variable exists in session to define the active role
        $auth = $this->session->get('auth');
        if (!$auth){
            $role = 'Guests';
        } else if($auth['isAdmin'] == 1) {
            $role = 'Admins';
        } else {
            $role = 'Users';
        }

        // Take the active controller/action from the dispatcher
        $controller = $dispatcher->getControllerName();
        $action = $dispatcher->getActionName();

        // Obtain the ACL list
        $acl = $this->getAcl();

        // Check if the Role have access to the controller (resource)
        $allowed = $acl->isAllowed($role, $controller, $action);
        if ($allowed != Acl::ALLOW) {
            if($role != 'Guests') {
                $this->flashSession->error("Permission denied. Log in as administrator.");
            } else {
                $this->flashSession->error("Please register or log in.");
            }

            $dispatcher->forward(
                [
                    'controller' => 'session',
                    'action'     => 'index'
                ]
            );

            return false;
        }
    }
}